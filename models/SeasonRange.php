<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "SeasonRange".
 *
 * @property int $idSeasonRange
 * @property int $season_idSeason
 * @property string $startDate
 * @property string $endDate
 *
 * @property Season $season
 */
class SeasonRange extends \yii\db\ActiveRecord
{
    public $vehicleType;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'SeasonRange';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['startDate', 'endDate'], 'required'],
            [['season_idSeason'], 'integer'],
            [['startDate', 'endDate'], 'string', 'max' => 5],
            [
                ['season_idSeason'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Season::class,
                'targetAttribute' => ['season_idSeason' => 'idSeason']
            ],
            [
                'endDate',
                'compare',
                'compareAttribute' => 'startDate',
                'operator' => '>=',
                'message' => Yii::t('app', 'End date must be later than start date.')
            ],
            [
                ['startDate', 'endDate'],
                'match',
                'pattern' => '/^\d{2}-\d{2}$/',
                'message' => Yii::t('app', 'Date format must be MM-DD.')
            ],
            ['startDate', 'validateDateRange'],
        ];
    }

    public function beforeValidate()
    {
        if (preg_match('/^(\d{2}).(\d{2})$/', $this->startDate, $matches)) {
            $this->startDate = $matches[2].'-'.$matches[1];
        }

        if (preg_match('/^(\d{2}).(\d{2})$/', $this->endDate, $matches)) {
            $this->endDate = $matches[2].'-'.$matches[1];
        }

        return parent::beforeValidate();
    }

    /**
     * Validates that this date range doesn't overlap with ranges from other seasons
     *
     * @param  string  $attribute  the attribute being validated
     * @param  array  $params  validation parameters
     */
    public function validateDateRange($attribute, $params)
    {
        // Skip validation if required attributes are not set
        if (empty($this->startDate) || empty($this->endDate)) {
            return;
        }

        // Check overlap with other seasons
        $overlappingSeasonRanges = self::find()
            ->joinWith('season')
            ->where([
                'Season.company_idCompany' => Yii::$app->user->getIdentity()->companyId,
                'Season.vehicletype_idVehicleType' => $this->vehicleType
            ]);

        if ($this->season_idSeason) {
            $overlappingSeasonRanges->andWhere(['!=', 'season_idSeason', $this->season_idSeason]);
        }

        foreach ($overlappingSeasonRanges->all() as $otherRange) {
            if ($this->isOverlapping($this->startDate, $this->endDate, $otherRange->startDate, $otherRange->endDate)) {
                $this->addError($attribute,
                    Yii::t('app', 'Date range overlaps with the date range of season "{seasonName}".',
                        ['seasonName' => $otherRange->season->name]));
                return;
            }
        }
    }

    /**
     * Validates that ranges within the same season don't overlap
     *
     * @param  array  $ranges  Array of SeasonRange models
     * @return bool Returns true if no overlaps found, false otherwise
     */
    public static function validateRange($ranges)
    {
        for ($i = 0; $i < count($ranges); $i++) {
            for ($j = $i + 1; $j < count($ranges); $j++) {
                if ($ranges[$i] instanceof SeasonRange && $ranges[$j] instanceof SeasonRange &&
                    $ranges[$i]->isOverlapping($ranges[$i]->startDate, $ranges[$i]->endDate, $ranges[$j]->startDate,
                        $ranges[$j]->endDate)) {
                    $ranges[$j]->addError('startDate', Yii::t('app', 'Date ranges must not overlap.'));
                    return false;
                }
            }
        }
        return true;
    }


    /**
     * @return string
     */
    public function humanFormated()
    {
        if (preg_match('/^(\d{2})-(\d{2})$/', $this->startDate, $matches)) {
            $startDate = $matches[2].'.'.$matches[1];
        } else {
            $startDate = $this->startDate;
        }

        if (preg_match('/^(\d{2})-(\d{2})$/', $this->endDate, $matches)) {
            $endDate = $matches[2].'.'.$matches[1];
        } else {
            $endDate = $this->endDate;
        }

        return $startDate.' - '.$endDate;
    }

    /**
     * Checks if two date ranges overlap.
     */
    private function isOverlapping($startA, $endA, $startB, $endB)
    {
        // Convert MM-DD format to integers for comparison (MM*100 + DD)
        $startAInt = $this->dateToInt($startA);
        $endAInt = $this->dateToInt($endA);
        $startBInt = $this->dateToInt($startB);
        $endBInt = $this->dateToInt($endB);


        // If range A fully covers range B
        if ($startAInt <= $startBInt && $endAInt >= $endBInt) {
            return true;
        }

        // If start of range A is inside B
        if ($startAInt >= $startBInt && $startAInt <= $endBInt) {
            return true;
        }

        // If end of range A is inside B
        if ($endAInt >= $startBInt && $endAInt <= $endBInt) {
            return true;
        }

        return false;
    }

    /**
     * Converts a date string in MM-DD format to an integer (MM*100 + DD)
     *
     * @param  string  $date  Date in MM-DD format
     * @return int Integer representation of the date
     */
    private function dateToInt($date)
    {
        if (preg_match('/^(\d{2})-(\d{2})$/', $date, $matches)) {
            return intval($matches[1]) * 100 + intval($matches[2]);
        }
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idSeasonRange' => '#',
            'season_idSeason' => Yii::t('app', 'Season'),
            'startDate' => Yii::t('app', 'Start date'),
            'endDate' => Yii::t('app', 'End date'),
        ];
    }

    /**
     * Gets query for [[SeasonIdSeason]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSeason()
    {
        return $this->hasOne(Season::class, ['idSeason' => 'season_idSeason']);
    }

    public function getStartDateHuman()
    {
        if (preg_match('/^(\d{2})-(\d{2})$/', $this->startDate, $matches)) {
            $startDate = $matches[2].'.'.$matches[1];
        } else {
            $startDate = $this->startDate;
        }

        return $startDate;
    }

    public function getEndDateHuman()
    {
        if (preg_match('/^(\d{2})-(\d{2})$/', $this->endDate, $matches)) {
            $endDate = $matches[2].'.'.$matches[1];
        } else {
            $endDate = $this->endDate;
        }

        return $endDate;
    }
}