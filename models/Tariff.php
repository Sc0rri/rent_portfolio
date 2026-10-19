<?php

namespace app\models;

use Yii;
use yii\caching\TagDependency;

/**
 * This is the model class for table "Tariff".
 *
 * @property int $idTariff
 * @property int $vehicle_idVehicle
 * @property int $season_idSeason
 * @property int $minDays
 * @property int $maxDays
 * @property float $price
 *
 * @property Vehicle $vehicle
 * @property Season $season
 */
class Tariff extends \yii\db\ActiveRecord
{
	const CACHE_DURATION = 300;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'Tariff';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['vehicle_idVehicle', 'season_idSeason', 'minDays', 'maxDays', 'price'], 'required'],
            [['vehicle_idVehicle', 'season_idSeason', 'minDays', 'maxDays'], 'integer'],
            [['price'], 'number'],
            [['vehicle_idVehicle'], 'exist', 'skipOnError' => true, 'targetClass' => Vehicle::class, 'targetAttribute' => ['vehicle_idVehicle' => 'idVehicle']],
            [['season_idSeason'], 'exist', 'skipOnError' => true, 'targetClass' => Season::class, 'targetAttribute' => ['season_idSeason' => 'idSeason']],
            // Ensure minDays is not greater than maxDays
            ['maxDays', 'compare', 'compareAttribute' => 'minDays', 'operator' => '>=', 'message' => Yii::t('app', 'The maximum number of days cannot be less than the minimum.')],
            // Custom validator to check for overlapping tariffs
            [['minDays', 'maxDays'], 'validateDaysRange'],
        ];
    }

    public function validateDaysRange($attribute)
    {
        if ($this->hasErrors()) {
            return;
        }

        // Only validate if both minDays and maxDays are set
        if ($this->minDays === null || $this->maxDays === null) {
            return;
        }

        $query = self::find()
            ->where(['vehicle_idVehicle' => $this->vehicle_idVehicle])
            ->andWhere(['season_idSeason' => $this->season_idSeason])
            ->andWhere([
                'or',
                // Case 1: Tariff range contains the new range
                ['and',
                    ['<=', 'minDays', $this->minDays],
                    ['>=', 'maxDays', $this->minDays]
                ],
                // Case 2: Tariff range is contained within the new range
                ['and',
                    ['>=', 'minDays', $this->minDays],
                    ['<=', 'maxDays', $this->maxDays]
                ],
                // Case 3: Tariff range overlaps the start of the new range
                ['and',
                    ['<=', 'minDays', $this->minDays],
                    ['<=', 'maxDays', $this->maxDays],
                    ['>=', 'maxDays', $this->minDays]
                ],
                // Case 4: Tariff range overlaps the end of the new range
                ['and',
                    ['>=', 'minDays', $this->minDays],
                    ['>=', 'maxDays', $this->maxDays],
                    ['<=', 'minDays', $this->maxDays]
                ]
            ]);

        // Exclude the current record in case of update
        if (!$this->isNewRecord) {
            $query->andWhere(['<>', 'idTariff', $this->idTariff]);
        }

        if ($query->exists()) {
            $this->addError($attribute, Yii::t('app','The day range overlaps with another rule for the same season and vehicle.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idTariff' => '#',
            'vehicle_idVehicle' => Yii::t('app', 'Vehicle'),
            'season_idSeason' => Yii::t('app', 'Season'),
            'minDays' => Yii::t('app', 'Minimum number of days'),
            'maxDays' => Yii::t('app', 'Maximum number of days'),
            'price' => Yii::t('app', 'Price per day'),
        ];
    }

    /**
     * Gets query for [[Vehicle]].
     *
     * @return ActiveQuery
     */
    public function getVehicle()
    {
        return $this->hasOne(Vehicle::class, ['idVehicle' => 'vehicle_idVehicle']);
    }

    /**
     * Gets query for [[Season]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSeason()
    {
        return $this->hasOne(Season::class, ['idSeason' => 'season_idSeason']);
    }

    /**
     * @param $vehicle_idVehicle
     * @param $dateFrom
     * @param $dateTo
     * @return array
     * @throws \Throwable
     */
    public static function calculatePrice($vehicle_idVehicle, $dateFrom, $dateTo)
    {
	    $cacheKey = 'vehicle_price_' . $vehicle_idVehicle . '_' . $dateFrom->format('Y-m-d') . '_' . $dateTo->format('Y-m-d');
	    $cachedData = Yii::$app->cache->get($cacheKey);
	    if ($cachedData !== false) {
		    return $cachedData;
	    }

        $result = [];
        $vehicle = Vehicle::find()->andWhere(['company_idCompany'=>Yii::$app->user->getIdentity()->companyId])->andWhere(['Vehicle.idVehicle'=>$vehicle_idVehicle])->one();
        if (!$vehicle) {
            $result['error'] = Yii::t('app', 'Vehicle not found');
            return $result;
        }

        // Set time to 00:00:00 to calculate days based only on date
        $dateFrom->setTime(0, 0, 0);
        $dateTo->setTime(0, 0, 0);

        // Get the month and day for checking season ranges
        $rentalMonth = $dateFrom->format('m');
        $rentalDay = $dateFrom->format('d');
        if ($rentalMonth==2 && $rentalDay==29) {
            $rentalDay = 28;
        }
        $datePattern = $rentalMonth . '-' . $rentalDay;

        // Find the season for the start date
	    $seasonCacheKey = 'season_' . Yii::$app->user->getIdentity()->companyId . '_' . $vehicle->brandmodel->vehicletype_idVehicleType . '_' . $datePattern;
	    $seasonRangeCached = Yii::$app->cache->get($seasonCacheKey);
	    if ($seasonRangeCached) {
		    $season_idSeason = $seasonRangeCached['idSeason'];
		    $season_name = $seasonRangeCached['name'];
		} else {
		    $query = new \yii\db\Query();
		    $seasonRange = $query->select(['sr.*', 's.idSeason', 's.name'])
			    ->from(['sr' => 'SeasonRange'])
			    ->innerJoin(['s' => 'Season'],
				    's.idSeason = sr.season_idSeason')
			    ->where(
				    [
					    's.company_idCompany' => Yii::$app->user->getIdentity(
					    )->companyId
				    ]
			    )
			    ->andWhere(
				    ['s.vehicletype_idVehicleType' => $vehicle->brandmodel->vehicletype_idVehicleType]
			    )
			    ->andWhere(['<=', 'sr.startDate', $datePattern])
			    ->andWhere(['>=', 'sr.endDate', $datePattern])
			    ->one();

		    if (!$seasonRange) {
			    $result['error'] = Yii::t(
				    'app',
				    'No season found for the given rental start date'
			    );
			    return $result;
		    }

		    Yii::$app->cache->set($seasonCacheKey, $seasonRange, self::CACHE_DURATION);

		    $season_idSeason = $seasonRange['idSeason'];
		    $season_name = $seasonRange['name'];
	    }

        $interval = $dateFrom->diff($dateTo);
        $days = $interval->days;

        //Check month pattern
        $monthTariff = TariffMonth::find()->where(['vehicle_idVehicle' => $vehicle_idVehicle, 'season_idSeason' => $season_idSeason])
            ->one();
        if ($monthTariff && $monthTariff->price!='') {
            $monthEndDate = clone $dateFrom;
            $startDay = (int)$dateFrom->format('d');

            // Get the next month and year
            $nextMonth = (int)$dateFrom->format('m') + 1;
            $nextYear = (int)$dateFrom->format('Y');

            // Adjust the year if the month is greater than 12
            if ($nextMonth > 12) {
                $nextMonth = 1;
                $nextYear++;
            }

            // Get the last day of the next month
            $lastDayOfNextMonth = (int)date('t', mktime(0, 0, 0, $nextMonth, 1, $nextYear));

            // If the original day is greater than the last day of the next month
            if ($startDay > $lastDayOfNextMonth) {
                // Set the last day of the next month
                $monthEndDate->setDate($nextYear, $nextMonth, $lastDayOfNextMonth);
                // Add one day (the day after the last day of the month)
                $monthEndDate->add(new \DateInterval('P1D'));
            } else {
                // Set the same day in the next month
                $monthEndDate->setDate($nextYear, $nextMonth, $startDay);
            }

            $price = 0;
            if ($dateTo == $monthEndDate) {
                $price = round($monthTariff->price, 2);
            } elseif ($dateTo > $monthEndDate) {
                $afterMonthInterval = $monthEndDate->diff($dateTo);

                $price = round($monthTariff->price + (($monthTariff->price/30)*$afterMonthInterval->days), 2);
            }

            if ($price>0) {
                $result['data'] = [
                    'days' => $days,
                    'season' => $season_name,
                    'pricePerDay' => round($price/$days, 2),
                    'totalPrice' => $price,
                ];

	            Yii::$app->cache->set($cacheKey, $result, self::CACHE_DURATION);

                return $result;
            }
        }

        // Calculate rental days
        // If start and end dates are the same, count as 1 day
        if ($days == 0) {
            $days = 1;
        }

        // Find the tariff based on season, vehicle model, and rental days
        $tariff = \app\models\Tariff::find()
            ->where(['vehicle_idVehicle' => $vehicle_idVehicle])
            ->andWhere(['season_idSeason' => $season_idSeason])
            ->andWhere(['<=', 'minDays', $days])
            ->andWhere(['>=', 'maxDays', $days])
            ->one();

        if (!$tariff) {
            if ($monthTariff && $monthTariff->price!='' && $monthTariff->useForCalculate==1) {
                $day_price = round($monthTariff->price/30, 2);
            } else {
                $result['error'] = Yii::t('app', 'No tariff found for the given number of days');

                return $result;
            }
        } else {
            $day_price = $tariff->price;
        }

        // Calculate the total price
        $totalPrice = round($days * $day_price, 2);

        // Prepare the successful response
        $result['data'] = [
            'days' => $days,
            'season' => $season_name,
            'pricePerDay' => (float)$day_price,
            'totalPrice' => $totalPrice,
        ];

	    Yii::$app->cache->set($cacheKey, $result, self::CACHE_DURATION);

	    return $result;
    }
}
