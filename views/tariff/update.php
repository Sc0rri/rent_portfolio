<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Tariff */
/* @var $vehicles array */
/* @var $seasons array */

$this->title = Yii::t('app', 'Update tariff');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Vehicle tariffs'), 'url' => ['index']];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="tariff-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model
    ]) ?>

</div>