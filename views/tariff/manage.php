<?php
use app\models\Brand;
use app\models\BrandModel;
use app\models\VehicleType;
use app\models\Vehicle;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $tariffs array */
/* @var $month_price float */
/* @var $month_calculate int */
/* @var $vehiclesArray array */
/* @var $seasonsArray array */
/* @var $selectedVehicleId integer */
/* @var $selectedSeasonId integer */
/* @var $from string */

$this->title = Yii::t('app', 'Manage tariffs');
$vehicle_urlCode = '';
if ($from=='vehicle') {
    $vehicle = Vehicle::findOne($selectedVehicleId);
    $vehicle_urlCode = $vehicle->urlCode;
    $this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Vehicle'), 'url' => ['vehicle/index']];
    $this->params['breadcrumbs'][] = ['label' => $vehicle->getName('full'), 'url' => ['vehicle/view','urlCode'=>$vehicle_urlCode]];
} else {
    $this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Vehicle tariffs'), 'url' => ['index']];
}
$this->params['breadcrumbs'][] = $this->title;

$vehicleName = $selectedVehicleId ? $vehiclesArray[$selectedVehicleId] : '';
$seasonName = $selectedSeasonId ? $seasonsArray[$selectedSeasonId] : '';
if ($selectedVehicleId && $selectedSeasonId) {
    $this->title = Yii::t('app', 'Manage tariffs: {vehicle}, {season}', [
        'vehicle' => $vehicleName,
        'season' => $seasonName,
    ]);
}

$this->registerJs('
$("#vehicle_vehicletype_select").on("change", function(){
        var expense_type = $("#expense-type_idexpensetype").val();
        var vehicle_type = $("#vehicle_vehicletype_select").val();
        
        $("#vehicle_model_select").empty().append("<option selected=\"selected\" value=\"\">-' . Yii::t('app', 'Any') . '-</option>");   
        $("#vehicle_select").empty().append("<option selected=\"selected\">-' . Yii::t('app', 'Select a vehicle') . '-</option>").trigger("change");
        $("#vehicle_brand_select").val("");
        
        $.get("' . Url::toRoute(['get-vehicles']) . '", {"idExpenseType":expense_type, "idVehicleType":vehicle_type}, function( data ) {
            var models = data.models; 
            models.forEach(function(model){
                $("#vehicle_model_select").append("<option value="+model.id+">"+model.name+"</option>");
            });
            
            var vehicles = data.vehicles;
            vehicles.forEach(function(vehicle){
                $("#vehicle_select").append("<option value="+vehicle.id+">"+vehicle.name+"</option>");
            });
        }, "json" );
});

$("#vehicle_brand_select").on("change", function(){
        var expense_type = $("#expense-type_idexpensetype").val();
        var vehicle_type = $("#vehicle_vehicletype_select").val();
        var brand_id =  $("#vehicle_brand_select").val();
        
        $("#vehicle_model_select").empty().append("<option selected=\"selected\" value=\"\">-' . Yii::t('app', 'Any') . '-</option>");   
        $("#vehicle_select").empty().append("<option selected=\"selected\">-' . Yii::t('app', 'Select a vehicle') . '-</option>").trigger("change");
        
        $.get("' . Url::toRoute(['get-vehicles']) . '", {"idExpenseType":expense_type, "idVehicleType":vehicle_type, "idBrand":brand_id}, function( data ) {
            var models = data.models; 
            models.forEach(function(model){
                $("#vehicle_model_select").append("<option value="+model.id+">"+model.name+"</option>");
            });
            
            var vehicles = data.vehicles;
            vehicles.forEach(function(vehicle){
                $("#vehicle_select").append("<option value="+vehicle.id+">"+vehicle.name+"</option>");
            });
        }, "json" );
});

$("#vehicle_model_select").on("change", function(){
        var expense_type = $("#expense-type_idexpensetype").val();
        var vehicle_type = $("#vehicle_vehicletype_select").val();
        var brand_id =  $("#vehicle_brand_select").val();
        var model_id =  $("#vehicle_model_select").val();
        
        $("#vehicle_select").empty().append("<option selected=\"selected\">-' . Yii::t('app', 'Select a vehicle') . '-</option>").trigger("change");
        
        $.get("' . Url::toRoute(['get-vehicles']) . '", {"idExpenseType":expense_type, "idVehicleType":vehicle_type, "idBrand":brand_id, "idBrandModel":model_id}, function( data ) {
            var vehicles = data.vehicles;
            vehicles.forEach(function(vehicle){
                $("#vehicle_select").append("<option value="+vehicle.id+">"+vehicle.name+"</option>");
            });
        }, "json" );
});

$("#vehicle_select").on("change", function(){
    var vehicle_id = $("#vehicle_select").val();
    var pre_season = $("#season_select").val();
    
    $("#season_select").empty().append("<option selected=\"selected\">' . Yii::t('app', 'Select a season') . '</option>").trigger("change");
    
    var found = false;
    $.get("' . Url::toRoute(['get-seasons']) . '", {"idVehicle":vehicle_id}, function( data ) {
            var seasons = data.seasons;
            seasons.forEach(function(season){
                $("#season_select").append("<option value="+season.id+">"+season.name+"</option>");
                if (season.id == pre_season) {
                    found = true;
                }
            });
            if (found) {
                $("#season_select").val(pre_season).trigger("change");
            }  
    }, "json" );
});

$("#season_select").on("change", function(){
    var vehicle_id = $("#vehicle_select").val();
    var season_id = $("#season_select").val();

    if (isNaN(vehicle_id)) {
        vehicle_id = false;
    }
    if (isNaN(season_id)) {
        season_id = false
    }
    
    if (vehicle_id && season_id) {
        $("#select_warning").hide();
        $("#tariff-form-container").load("'.Url::to(['tariff/manage']).'?season_id="+season_id+"&vehicle_id="+vehicle_id+" #tariff-manage-form");
    } else {
        $("#tariff-form-container").html("");
        $("#select_warning").show();
    }
});

$(document).on("click", "#add-tariff-btn", function() {
    currentIndex = $(this).data("index");
    currentIndex++;
    newRow = `
            <tr class="tariff-row" data-index="` + currentIndex + `">
                <td>
                    <input type="number" id="tariff-` + currentIndex + `-mindays" class="form-control" name="Tariff[` + currentIndex + `][minDays]" min="1" data-index="` + currentIndex + `" onchange="validateMinMax(this)">
                </td>
                <td>
                    <input type="number" id="tariff-` + currentIndex + `-maxdays" class="form-control" name="Tariff[` + currentIndex + `][maxDays]" min="1" data-index="` + currentIndex + `" onchange="validateMinMax(this)">
                </td>
                <td>
                    <input type="text" id="tariff-` + currentIndex + `-price" class="form-control" name="Tariff[` + currentIndex + `][price]">
                </td>
                <td>
                    <button type="button" class="btn btn-danger remove-tariff" onclick="removeTariff(this)">
                        <i class="glyphicon glyphicon-trash"></i>
                    </button>
                </td>
            </tr>
    `;
    $("#tariff-container").append(newRow);
    $(this).data("index", currentIndex)
});

// Remove tariff row
window.removeTariff = function(button) {
    $(button).closest(".tariff-row").remove();
};

// Validate min and max days
window.validateMinMax = function(input) {
    const index = $(input).data("index");
    const minInput = $(`input[name="Tariff[` + index + `][minDays]"]`);
    const maxInput = $(`input[name="Tariff[` + index + `][maxDays]"]`);
    
    const minDays = parseInt(minInput.val());
    const maxDays = parseInt(maxInput.val());
    
    if (minDays > maxDays && !isNaN(maxDays) && !isNaN(minDays)) {
        if ($(input).attr("id").includes("mindays")) {
            maxInput.val(minDays);
        } else {
            minInput.val(maxDays);
        }
    }
};

$(document).on("submit", "#tariff-manage-form", function(e) {
    e.preventDefault();
    
    let valid = true;
    let errorMessage = "";

    // Check for empty fields
    $(".tariff-row").each(function() {
        const minDays = $(this).find("input[name*=\'[minDays]\']").val();
        const maxDays = $(this).find("input[name*=\'[maxDays]\']").val();
        const price = $(this).find("input[name*=\'[price]\']").val();

        if ((minDays || maxDays || price) && (!minDays || !maxDays || !price)) {
            valid = false;
            errorMessage = "'.Yii::t("app", "All tariff fields must be filled in").'";
            return false;
        }
    });
    
    // Show error if validation fails
    if (!valid) {
        alert(errorMessage);
        return false;
    }
    
    // Submit form via AJAX
    $.ajax({
        url: $(this).attr("action"),
        type: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function(response) {
            if (response.success) {
                // Check if we need to redirect to vehicle page
                const fromValue = $("#tariff-manage-form").data("from");
                if (fromValue === "vehicle") {
                    const vehicleUrlCode = $("#tariff-manage-form").data("vehicle-url");
                    window.location.href = "'.yii\helpers\Url::to(["vehicle/view","urlCode"=>$vehicle_urlCode, "#"=>"tariffs"]).'";
                } else {
                    $("#tariff-form-container").html(response.html);
                    alert("'.Yii::t("app", "Tariffs saved successfully.").'");
                }
            } else {
                $("#tariff-form-container").html(response.html);
            }
        },
        error: function() {
            alert("'.Yii::t("app", "An error occurred while saving the tariffs.").'");
        }
    });
});

');
?>
<div class="tariff-manage">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if ($from!='vehicle') {?>
        <div class="row">
            <div class="col-md-12">
                <?php $form = ActiveForm::begin(['id' => 'tariff-selector-form', 'action' => ['tariff/manage'], 'method' => 'get']); ?>
                <?php
                $types_array = [];
                $brands_array = [];
                $models_array = [];
                $vehicles_array = [];

                $types = VehicleType::find()->all();
                if (count($types)) {
                    foreach ($types as $type) {
                        $types_array[$type->idVehicleType] = $type->getTitle(true);
                    }
                }

                $brands = Brand::find()
                    ->leftJoin('BrandModel', 'BrandModel.brand_idBrand = Brand.idBrand')
                    ->leftJoin('Vehicle', 'Vehicle.brandmodel_idBrandModel = BrandModel.idBrandModel AND Vehicle.company_idCompany=' . Yii::$app->user->getIdentity()->companyId)
                    ->andWhere(['is not', 'idVehicle', null])
                    ->orderBy('name ASC')
                    ->all();
                if (count($brands)) {
                    foreach ($brands as $one_brand) {
                        $brands_array[$one_brand->idBrand] =  $one_brand->name;
                    }
                }

                $models = BrandModel::find()
                    ->leftJoin('Vehicle', 'Vehicle.brandmodel_idBrandModel = idBrandModel AND company_idCompany=' . Yii::$app->user->getIdentity()->companyId)
                    ->andWhere(['is not', 'idVehicle', null])
                    ->orderBy('name ASC')
                    ->all();
                if (count($models)) {
                    foreach ($models as $one_model) {
                        $models_array[$one_model->idBrandModel] =  $one_model->name;
                    }
                }

                $vehicles = Vehicle::find()->andWhere(['company_idCompany'=>Yii::$app->user->getIdentity()->companyId])->joinWith('brandmodel.brand')->orderBy('Brand.name,BrandModel.name,num')->all();
                if (count($vehicles)) {
                    foreach ($vehicles as $vehicle) {
                        $vehicles_array[$vehicle->idVehicle] = $vehicle->getName('full');
                    }
                }
                ?>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label" for="vehicle_vehicletype_select"><?= Yii::t('app', 'Vehicle type');?></label>
                            <?= Html::dropDownList('vehicle_vehicletype', null, [''=>Yii::t('app', 'Any')]+$types_array, ['class'=>'form-control', 'id'=>'vehicle_vehicletype_select']); ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label" for="vehicle_brand_select"><?= Yii::t('app', 'Brand');?></label>
                            <?= Html::dropDownList('vehicle_brand', null, [''=>Yii::t('app', 'Any')]+$brands_array, ['class'=>'form-control', 'id'=>'vehicle_brand_select']); ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label" for="vehicle_model_select"><?= Yii::t('app', 'Model');?></label>
                            <?= Html::dropDownList('vehicle_model', null, [''=>Yii::t('app', 'Any')]+$models_array, ['class'=>'form-control', 'id'=>'vehicle_model_select']); ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label><?= Yii::t('app', 'Vehicle') ?></label>
                    <?= Html::dropDownList('vehicle_id', $selectedVehicleId, $vehiclesArray, [
                            'class' => 'form-control',
                            'id' => 'vehicle_select',
                            'prompt' => Yii::t('app', 'Select a vehicle'),
                    ]) ?>
                </div>

                <div class="form-group">
                    <label><?= Yii::t('app', 'Season') ?></label>
                    <?= Html::dropDownList('season_id', $selectedSeasonId, $seasonsArray, [
                            'class' => 'form-control',
                            'id' => 'season_select',
                            'prompt' => Yii::t('app', 'Select a season')
                    ]) ?>
                </div>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
    <?php } ?>

    <div id="tariff-form-container">
        <?php if ($selectedVehicleId && $selectedSeasonId) {
            echo $this->render('_tariffs_form', [
                    'from' => $from,
                    'vehicle_urlCode' => $vehicle_urlCode,
                    'tariffs' => $tariffs,
                    'month_price' => $month_price,
                    'month_calculate' => $month_calculate,
                    'selectedVehicleId' => $selectedVehicleId,
                    'selectedSeasonId' => $selectedSeasonId
            ]);
        } ?>
    </div>

    <div class="alert alert-info <?= ($selectedVehicleId && $selectedSeasonId)?'hidden':''; ?>" id="select_warning">
        <?= Yii::t('app', 'Select a vehicle and a season to manage tariffs') ?>
    </div>

</div>