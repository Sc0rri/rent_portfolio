<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $from string */
/* @var $vehicle_urlCode array */
/* @var $tariffs array */
/* @var $month_price float */
/* @var $month_calculate int */
/* @var $selectedVehicleId integer */
/* @var $selectedSeasonId integer */

$index = 0;
$form = ActiveForm::begin([
        'id' => 'tariff-manage-form',
        'action' => ['tariff/ajax-save-tariffs'],
        'options' => [
            'data-from' => $from
        ]]);
    echo Html::hiddenInput('vehicle_id', $selectedVehicleId);
    echo Html::hiddenInput('season_id', $selectedSeasonId);
    echo Html::hiddenInput('from', $from)
    ?>
    <div class="form-group tariff-month">
        <label class="control-label" for="month_price"><?= Yii::t('app', 'Price per month');?></label>
        <?= Html::textInput('month_price', $month_price, ['id'=>'month_price', 'class'=>'form-control']); ?>
        <?= Html::checkbox('month_calculate', $month_calculate, ['id'=>'month_calculate', 'label'=>Yii::t('app', 'Use the monthly price to calculate the price for rentals shorter than a month')]); ?>
    </div>


    <div class="tariff-items">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th><?= Yii::t('app', 'Minimum number of days') ?></th>
                <th><?= Yii::t('app', 'Maximum number of days') ?></th>
                <th><?= Yii::t('app', 'Price per day') ?></th>
                <th></th>
            </tr>
            </thead>
            <tbody id="tariff-container">
            <?php foreach ($tariffs as $index => $tariff): ?>
                <tr class="tariff-row" data-index="<?= $index ?>">
                    <?php if ($tariff->idTariff): ?>
                        <input type="hidden" name="Tariff[<?= $index ?>][idTariff]" value="<?= $tariff->idTariff ?>">
                    <?php endif; ?>
                    <td>
                        <?= Html::activeTextInput($tariff, "[$index]minDays", [
                            'class' => 'form-control',
                            'type' => 'number',
                            'min' => '1',
                            'data-index' => $index,
                            'onchange' => 'validateMinMax(this)',
                        ]) ?>
                        <?= $form->field($tariff, 'minDays', ['template'=>'{error}'])->hiddenInput() ?>
                    </td>
                    <td>
                        <?= Html::activeTextInput($tariff, "[$index]maxDays", [
                            'class' => 'form-control',
                            'type' => 'number',
                            'min' => '1',
                            'data-index' => $index,
                            'onchange' => 'validateMinMax(this)',
                        ]) ?>
                        <?= $form->field($tariff, 'maxDays', ['template'=>'{error}'])->hiddenInput() ?>
                    </td>
                    <td>
                        <?= Html::activeTextInput($tariff, "[$index]price", [
                            'class' => 'form-control',
                        ]) ?>
                        <?= $form->field($tariff, 'price', ['template'=>'{error}'])->hiddenInput() ?>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger remove-tariff" onclick="removeTariff(this)">
                            <i class="glyphicon glyphicon-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
            <tr>
                <td colspan="4" class="text-right">
                    <button type="button" class="btn btn-success" id="add-tariff-btn" data-index="<?= $index; ?>">
                        <i class="glyphicon glyphicon-plus"></i> <?= Yii::t('app', 'Add tariff') ?>
                    </button>
                </td>
            </tfoot>
        </table>
    </div>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-primary']) ?>
    </div>

<?php ActiveForm::end(); ?>