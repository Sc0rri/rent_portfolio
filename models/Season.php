<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "Season".
 *
 * @property int $idSeason
 * @property int $company_idCompany
 * @property string $name
 * @property int $vehicletype_idVehicleType
 *
 * @property SeasonRange[] $seasonRanges
 * @property Tariff[] $tariffs
 * @property VehicleType $vehicletype
 */
class Season extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'Season';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['company_idCompany', 'vehicletype_idVehicleType'], 'integer'],
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['vehicletype_idVehicleType'], 'exist', 'skipOnError' => true, 'targetClass' => VehicleType::className(), 'targetAttribute' => ['vehicletype_idVehicleType' => 'idVehicleType']],
            [['vehicletype_idVehicleType'], 'validateTariff']
        ];
    }

    public function validateTariff($attribute, $params)
    {
        if (isset($this->oldAttributes['vehicletype_idVehicleType']) && $this->vehicletype_idVehicleType == $this->oldAttributes['vehicletype_idVehicleType']) return;

        if (count($this->tariffs)>0) {
            $this->addError($attribute, Yii::t('app', 'Changing vehicle type is not available — the current season already has tariffs.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idSeason' => '#',
            'company_idCompany' => Yii::t('app', 'Company'),
            'name' => Yii::t('app', 'Name'),
            'vehicletype_idVehicleType' => Yii::t('app', 'Vehicle type'),
        ];
    }

    /**
     * Gets query for [[SeasonRanges]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSeasonRanges()
    {
        return $this->hasMany(SeasonRange::class, ['season_idSeason' => 'idSeason'])->orderBy('startDate');
    }

    /**
     * Gets query for [[Tariffs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTariffs()
    {
        return $this->hasMany(Tariff::class, ['season_idSeason' => 'idSeason']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVehicletype()
    {
        return $this->hasOne(VehicleType::className(), ['idVehicleType' => 'vehicletype_idVehicleType']);
    }

    public function canDelete()
    {
        return count($this->tariffs)==0;
    }

    public function beforeSave($insert)
    {
        if (!$this->company_idCompany) {
            $this->company_idCompany = Yii::$app->user->getIdentity()->companyId;
        }

        return parent::beforeSave($insert);
    }
}
