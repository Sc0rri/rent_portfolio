<?php

use app\models\Vehicle;
use app\models\Season;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel app\models\TariffSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Vehicle tariffs');
$this->params['breadcrumbs'][] = $this->title;

$vehicles_array = [];
$vehicles = Vehicle::find()->andWhere(['company_idCompany'=>Yii::$app->user->getIdentity()->companyId])->joinWith('brandmodel.brand')->orderBy('Brand.name,BrandModel.name,num')->all();
foreach ($vehicles as $one_vehicle) {
    $vehicles_array[$one_vehicle->idVehicle] = $one_vehicle->getName('full');
}

$seasons_array = [];
$seasons = Season::find()->andWhere(['company_idCompany'=>Yii::$app->user->getIdentity()->companyId])->orderBy('name')->all();
foreach ($seasons as $one_season) {
    $seasons_array[$one_season->idSeason] = $one_season->name;
}
?>
<div class="tariff-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'Add tariff'), ['create'], ['class' => 'btn btn-success']) ?>
        <?= Html::a(Yii::t('app', 'Manage tariffs'), ['manage'], ['class' => 'btn btn-primary']) ?>
    </p>

    <?php Pjax::begin(); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute' => 'vehicle_idVehicle',
                'format'=>'raw',
                'value' => function ($model) {
                    if ($model->vehicle) {
                        return Html::a($model->vehicle->getName('full'), ['vehicle/view','urlCode'=>$model->vehicle->urlCode]);
                    } else {
                        return '';
                    }
                },
                'filter' => Html::activeDropDownList($searchModel, 'vehicle_idVehicle', $vehicles_array, ['class'=>'form-control','prompt' => Yii::t('app', 'Select a vehicle')]),
            ],
            [
                'attribute' => 'season_idSeason',
                'value' => function ($model) {
                    return $model->season->name ?? '';
                },
                'filter' => Html::activeDropDownList($searchModel, 'season_idSeason', $seasons_array, ['class'=>'form-control','prompt' => Yii::t('app', 'Select a season')]),
            ],
            'minDays',
            'maxDays',
            [
                'attribute' => 'price',
                'format' => 'currency',
            ],

            [
                    'class' => 'yii\grid\ActionColumn',
                'template' => '{update}{delete}',
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>