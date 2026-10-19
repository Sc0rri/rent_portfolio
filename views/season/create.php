<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Season $model */
/** @var app\models\SeasonRange[] $seasonRanges */

$this->title = Yii::t('app', 'Add season');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Seasons'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="season-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'seasonRanges' => $seasonRanges
    ]) ?>

</div>
