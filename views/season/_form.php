<?php

use app\models\VehicleType;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\datetime\DateTimePicker;

/* @var $this yii\web\View */
/* @var $model app\models\Season */
/* @var $seasonRanges app\models\SeasonRange[] */
/* @var $form yii\widgets\ActiveForm */
?>

    <div class="season-form">

        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'vehicletype_idVehicleType')->dropDownList(VehicleType::getList(true)); ?>

        <div id="ranges" class="form-group mt-5">
            <?php foreach ($seasonRanges as $i => $range): ?>
                <div class="range-block row mb-1">
                    <?php if ($range->idSeasonRange): ?>
                        <input type="hidden" name="SeasonRange[<?= $i ?>][idSeasonRange]" value="<?= $range->idSeasonRange ?>">
                    <?php endif; ?>
                    <div class="col-sm-5">
                        <?= DateTimePicker::widget([
                            'name' => "SeasonRange[$i][startDate]",
                            'type' => DateTimePicker::TYPE_INPUT,
                            'value' => $range->startDateHuman,
                            'options' => ['placeholder' => 'dd.mm', 'class' => 'form-control'],
                            'pluginOptions' => [
                                'autoclose' => true,
                                'format' => 'dd.mm',
                                'todayHighlight' => true,
                                'startView' => 3,
                                'minView' => 2
                            ],
                        ]) ?>
                        <?= $form->field($range, 'startDate', ['template'=>'{error}'])->hiddenInput() ?>
                    </div>
                    <div class="col-sm-5">
                        <?= DateTimePicker::widget([
                            'name' => "SeasonRange[$i][endDate]",
                            'type' => DateTimePicker::TYPE_INPUT,
                            'value' => $range->endDateHuman,
                            'options' => ['placeholder' => 'dd.mm', 'class' => 'form-control'],
                            'pluginOptions' => [
                                'autoclose' => true,
                                'format' => 'dd.mm',
                                'todayHighlight' => true,
                                'startView' => 3,
                                'minView' => 2
                            ],
                        ]) ?>
                        <?= $form->field($range, 'endDate', ['template'=>'{error}'])->hiddenInput() ?>
                    </div>
                    <?php if ($i != 0): ?>
                        <div class="col-sm-2">
                            <button type="button" class="remove-range btn btn-danger btn-sm"><?= Yii::t('app', 'Delete') ?></button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="form-group">
            <button type="button" id="add-range" class="btn btn-success btn-sm">
                <i class="fa fa-plus"></i> <?= Yii::t('app', 'Add range') ?>
            </button>
        </div>

        <div class="form-group mt-5">
            <?=  Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Save'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>

        <p><?= Yii::t('app', 'Rules for adding seasons'); ?></p>

        <?php ActiveForm::end(); ?>
    </div>

<?php
$dateTimePickerOptions = json_encode([
    'autoclose' => true,
    'format' => 'dd.mm',
    'todayHighlight' => true,
    'startView' => 3,
    'minView' => 2,
    'language' => Yii::$app->language
]);

$deleteButtonText = Yii::t('app', 'Delete');
$js = <<<JS
    $('#add-range').on('click', function () {
        var index = $('#ranges .range-block').length;
        var dateTimePickerOptions = {$dateTimePickerOptions};
        
        var newRange = $('<div class="range-block row mb-1">' +
            '<div class="col-sm-5">' +
                '<input type="text" name="SeasonRange[' + index + '][startDate]" class="form-control datetimepicker_input" placeholder="dd.mm">' +
                '<div class="form-group"></div>' +
            '</div>' +
            '<div class="col-sm-5">' +
                '<input type="text" name="SeasonRange[' + index + '][endDate]" class="form-control datetimepicker_input" placeholder="dd.mm">' +
                '<div class="form-group"></div>' +
            '</div>' +
            '<div class="col-sm-2">' +
                '<button type="button" class="remove-range btn btn-danger btn-sm">{$deleteButtonText}</button>' +
            '</div>' +
        '</div>');
        
        $('#ranges').append(newRange);
        
        newRange.find('.datetimepicker_input').each(function() {
            $(this).datetimepicker(dateTimePickerOptions);
        });
    });

    $(document).on('click', '.remove-range', function () {
        $(this).closest('.range-block').remove();
    });
JS;

$this->registerJs($js);
?>