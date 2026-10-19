<?php
use app\models\Brand;
use app\models\BrandModel;
use app\models\Season;
use app\models\VehicleType;
use app\models\Vehicle;
use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Tariff */
/* @var $form yii\widgets\ActiveForm */
/* @var $vehicles array */
/* @var $seasons array */

$seasons_array = [];
if ($model->vehicle_idVehicle) {
    $seasons = Season::find()->andWhere(['company_idCompany' => Yii::$app->user->getIdentity()->companyId])->andWhere(['vehicleType_idVehicleType' => $model->vehicle->brandmodel->vehicletype_idVehicleType])->all();
    
    if (count($seasons)) {
        foreach ($seasons as $season) {
            $seasons_array[$season->idSeason] = $season->name;
        }
    }
}

$this->registerJs('
$("#vehicle_vehicletype_select").on("change", function(){
        var expense_type = $("#expense-type_idexpensetype").val();
        var vehicle_type = $("#vehicle_vehicletype_select").val();
        
        $("#vehicle_model_select").empty().append("<option selected=\"selected\" value=\"\">-' . Yii::t('app', 'Any') . '-</option>");   
        $("#tariff-vehicle_idvehicle").empty().append("<option selected=\"selected\">-' . Yii::t('app', 'Select a vehicle') . '-</option>");
        $("#vehicle_brand_select").val("");
        
        $.get("' . Url::toRoute(['get-vehicles']) . '", {"idExpenseType":expense_type, "idVehicleType":vehicle_type}, function( data ) {
            var models = data.models; 
            models.forEach(function(model){
                $("#vehicle_model_select").append("<option value="+model.id+">"+model.name+"</option>");
            });
            
            var vehicles = data.vehicles;
            vehicles.forEach(function(vehicle){
                $("#tariff-vehicle_idvehicle").append("<option value="+vehicle.id+">"+vehicle.name+"</option>");
            });
            $("#tariff-vehicle_idvehicle").trigger("change");
        }, "json" );
});

$("#vehicle_brand_select").on("change", function(){
        var expense_type = $("#expense-type_idexpensetype").val();
        var vehicle_type = $("#vehicle_vehicletype_select").val();
        var brand_id =  $("#vehicle_brand_select").val();
        
        $("#vehicle_model_select").empty().append("<option selected=\"selected\" value=\"\">-' . Yii::t('app', 'Any') . '-</option>");   
        $("#tariff-vehicle_idvehicle").empty().append("<option selected=\"selected\">-' . Yii::t('app', 'Select a vehicle') . '-</option>");
        
        $.get("' . Url::toRoute(['get-vehicles']) . '", {"idExpenseType":expense_type, "idVehicleType":vehicle_type, "idBrand":brand_id}, function( data ) {
            var models = data.models; 
            models.forEach(function(model){
                $("#vehicle_model_select").append("<option value="+model.id+">"+model.name+"</option>");
            });
            
            var vehicles = data.vehicles;
            vehicles.forEach(function(vehicle){
                $("#tariff-vehicle_idvehicle").append("<option value="+vehicle.id+">"+vehicle.name+"</option>");
            });
            $("#tariff-vehicle_idvehicle").trigger("change");
        }, "json" );
});

$("#vehicle_model_select").on("change", function(){
        var expense_type = $("#expense-type_idexpensetype").val();
        var vehicle_type = $("#vehicle_vehicletype_select").val();
        var brand_id =  $("#vehicle_brand_select").val();
        var model_id =  $("#vehicle_model_select").val();
        
        $("#tariff-vehicle_idvehicle").empty().append("<option selected=\"selected\">-' . Yii::t('app', 'Select a vehicle') . '-</option>");
        
        $.get("' . Url::toRoute(['get-vehicles']) . '", {"idExpenseType":expense_type, "idVehicleType":vehicle_type, "idBrand":brand_id, "idBrandModel":model_id}, function( data ) {
            var vehicles = data.vehicles;
            vehicles.forEach(function(vehicle){
                $("#tariff-vehicle_idvehicle").append("<option value="+vehicle.id+">"+vehicle.name+"</option>");
            });
            $("#tariff-vehicle_idvehicle").trigger("change");
        }, "json" );
});

$("#tariff-vehicle_idvehicle").on("change", function(){
    var vehicle_id = $("#tariff-vehicle_idvehicle").val();
    var pre_season = $("#tariff-season_idseason").val();
    
    $("#tariff-season_idseason").empty().append("<option selected=\"selected\">' . Yii::t('app', 'Select a season') . '</option>");
    
    var found = false;
    $.get("' . Url::toRoute(['get-seasons']) . '", {"idVehicle":vehicle_id}, function( data ) {
            var seasons = data.seasons;
            seasons.forEach(function(season){
                $("#tariff-season_idseason").append("<option value="+season.id+">"+season.name+"</option>");
                if (season.id == pre_season) {
                    found = true;
                }
            });
            if (found) {
                $("#tariff-season_idseason").val(pre_season);
            }  
    }, "json" );
});
');
?>

    <div class="tariff-form">

    <?php $form = ActiveForm::begin(); ?>

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
        <?= $form->field($model, 'vehicle_idVehicle')->dropDownList([null=>Yii::t('app', 'Select a vehicle')]+$vehicles_array); ?>

        <?= $form->field($model, 'season_idSeason')->dropDownList([null=>Yii::t('app', 'Select a season')]+$seasons_array) ?>

        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'minDays')->textInput(['type' => 'number', 'min' => 1]) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'maxDays')->textInput(['type' => 'number', 'min' => 1]) ?>
            </div>
        </div>

        <?= $form->field($model, 'price')->textInput() ?>

        <div class="form-group">
           <?=  Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Save'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

<?php
// Add client-side validation for min/max days
$js = <<<JS
    $('#tariff-mindays').on('change', function() {
        var minDays = parseInt($(this).val());
        var maxDays = parseInt($('#tariff-maxdays').val());
        
        if (minDays > maxDays && maxDays > 0) {
            $('#tariff-maxdays').val(minDays);
        }
    });
    
    $('#tariff-maxdays').on('change', function() {
        var maxDays = parseInt($(this).val());
        var minDays = parseInt($('#tariff-mindays').val());
        
        if (minDays > maxDays && minDays > 0) {
            $(this).val(minDays);
        }
    });
JS;

$this->registerJs($js);
?>