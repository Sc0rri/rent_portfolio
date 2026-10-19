<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\TariffMonth;

/**
 * TariffMonthSearch represents the model behind the search form of `app\models\Tariff`.
 */
class TariffMonthSearch extends TariffMonth
{
    var $company_idCompany;
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idTariffMonth', 'vehicle_idVehicle', 'season_idSeason', 'useForCalculate'], 'integer'],
            [['price'], 'number'],
            [['company_idCompany'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = TariffMonth::find();

        $query->joinWith(['vehicle', 'season']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'vehicle_idVehicle' => SORT_ASC,
                    'season_idSeason' => SORT_ASC,
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        if (!empty($this->company_idCompany)) {
            $query->andWhere(['Vehicle.company_idCompany' => $this->company_idCompany]);
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'idTariffMonth' => $this->idTariffMonth,
            'vehicle_idVehicle' => $this->vehicle_idVehicle,
            'season_idSeason' => $this->season_idSeason,
            'price' => $this->price,
        ]);

        return $dataProvider;
    }
}