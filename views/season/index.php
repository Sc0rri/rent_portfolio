<?php

use app\models\Season;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Seasons');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="season-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'Add season'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>


    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'name',
            [
                'attribute' => 'vehicletype_idVehicleType',
                'format'=>'html',
                'value' => function ($data) {
                    if ($data->vehicletype) {
                        return $data->vehicletype->getTitle(true);
                    }
                    return '';
                }
            ],
            [
                'attribute' => 'dateRanges',
                'label' => Yii::t('app', 'Periods'),
                'format' => 'raw',
                'value' => function ($model) {
                    /* @var $model app\models\Season */
                    $ranges = $model->getSeasonRanges()
                        ->orderBy(['startDate' => SORT_ASC])
                        ->all();

                    if (!empty($ranges)) {
                        $html = '<ul class="list-unstyled mb-0">';
                        foreach ($ranges as $range) {
                            $html .= '<li>' . $range->humanFormated() . '</li>';
                        }
                        $html .= '</ul>';
                    } else {
                        return '';
                    }

                    return $html;
                }
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{update} {delete}',
                'buttons'=>[
                    'delete' => function ($url, $model) {
                        if ($model->canDelete()) {
                            return Html::a('<span class="fa fa-trash"></span>', $url, [
                                'title' => Yii::t('yii', 'Delete'),
                                'aria-label' => Yii::t('yii', 'Delete'),
                                'data' => [
                                    'pjax' => '0',
                                    'confirm' => Yii::t('app', 'Are you sure you want to delete this?'),
                                    'method' => 'post'
                                ]
                            ]);
                        } else {
                            return Html::a('<span class="fa fa-trash"></span>', false, [
                                'title' => Yii::t('yii', 'Delete'),
                                'aria-label' => Yii::t('yii', 'Delete'),
                                'data' => [
                                    'alert' => Yii::t('app', 'Cannot delete the season because it is linked to tariffs!'),
                                ]
                            ]);
                        }
                    },
                ]
            ],
        ],
    ]); ?>


</div>