<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "TariffMonth".
 *
 * @property int $idTariffMonth
 * @property int $vehicle_idVehicle
 * @property int $season_idSeason
 * @property float $price
 * @property int $useForCalculate
 *
 * @property Vehicle $vehicle
 * @property Season $season
 */
class TariffMonth extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'TariffMonth';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['vehicle_idVehicle', 'season_idSeason', 'price'], 'required'],
            [['vehicle_idVehicle', 'season_idSeason', 'useForCalculate'], 'integer'],
            [['price'], 'number'],
            [['vehicle_idVehicle'], 'exist', 'skipOnError' => true, 'targetClass' => Vehicle::class, 'targetAttribute' => ['vehicle_idVehicle' => 'idVehicle']],
            [['season_idSeason'], 'exist', 'skipOnError' => true, 'targetClass' => Season::class, 'targetAttribute' => ['season_idSeason' => 'idSeason']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idTariffMonth' => '#',
            'vehicle_idVehicle' => Yii::t('app', 'Vehicle'),
            'season_idSeason' => Yii::t('app', 'Season'),
            'price' => Yii::t('app', 'Price'),
            'useForCalculate' => Yii::t('app', 'Use the monthly price to calculate the price for rentals shorter than a month'),
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
}
