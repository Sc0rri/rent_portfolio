<?php

namespace app\controllers;

use app\models\Brand;
use app\models\BrandModel;
use app\models\ExpenseType;
use app\models\SeasonRange;
use app\models\TariffMonth;
use app\models\VehicleType;
use Yii;
use app\models\Tariff;
use app\models\TariffSearch;
use app\models\Vehicle;
use app\models\Season;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * TariffController implements the CRUD actions for Tariff model.
 */
class TariffController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['index', 'create', 'update', 'delete', 'manage', 'get-vehicles', 'get-seasons', 'ajax-save-tariffs'],
                        'allow' => true,
                        'roles' => ['owner'],
                    ]
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Tariff models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new TariffSearch();
        $searchModel->company_idCompany=Yii::$app->user->getIdentity()->companyId;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new Tariff model.
     * If creation is successful, the browser will be redirected to the 'index' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Tariff();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model
        ]);
    }

    /**
     * Updates an existing Tariff model.
     * If update is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model
        ]);
    }

    /**
     * Deletes an existing Tariff model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Manages tariffs for a specific vehicle and season.
     * @return mixed
     */
    public function actionManage()
    {
        $vehicleId = Yii::$app->request->get('vehicle_id');
        $seasonId = Yii::$app->request->get('season_id');
        $from = Yii::$app->request->get('from', '');

        // Load existing tariffs if vehicle and season are selected
        $tariffs = [];
        $month_price = '';
        $month_calculate = 0;
        if ($vehicleId && $seasonId) {
            $tariffs = Tariff::find()
                ->where(['vehicle_idVehicle' => $vehicleId, 'season_idSeason' => $seasonId])
                ->orderBy('minDays')
                ->all();

            // If no tariffs exist, create an empty one for the form
            if (empty($tariffs)) {
                $tariffs[] = new Tariff();
            }

            $monthTariff = TariffMonth::find()->where(['vehicle_idVehicle' => $vehicleId, 'season_idSeason' => $seasonId])
                ->one();
            if ($monthTariff) {
                $month_price = $monthTariff->price;
                $month_calculate = $monthTariff->useForCalculate;
            }
        } else {
            // Default empty tariff for the form
            $tariffs[] = new Tariff();
        }

        // Prepare data for the form
        $vehicles = Vehicle::find()
            ->where(['company_idCompany' => Yii::$app->user->identity->companyId])
            ->joinWith('brandmodel.brand')
            ->orderBy('Brand.name, BrandModel.name, num')
            ->all();
        $vehiclesArray = ArrayHelper::map($vehicles, 'idVehicle', function ($model) {
            return $model->getName('full');
        });

        $seasonsArray = [];
        if ($vehicleId) {
            $vehicle = Vehicle::findOne(['idVehicle' => $vehicleId, 'company_idCompany' => Yii::$app->user->identity->companyId]);
            if ($vehicle) {
                $seasons = Season::find()
                    ->where(['company_idCompany' => Yii::$app->user->identity->companyId])
                    ->andWhere(['vehicleType_idVehicleType' => $vehicle->brandmodel->vehicletype_idVehicleType])
                    ->orderBy('name')
                    ->all();
                $seasonsArray = ArrayHelper::map($seasons, 'idSeason', 'name');
            } else {
                $vehicleId = null;
            }
        }

        if (empty($seasonsArray)) {
            $seasonId = null;
        }

        return $this->render('manage', [
            'from' => $from,
            'tariffs' => $tariffs,
            'month_price' => $month_price,
            'month_calculate' => $month_calculate,
            'vehiclesArray' => $vehiclesArray,
            'seasonsArray' => $seasonsArray,
            'selectedVehicleId' => $vehicleId,
            'selectedSeasonId' => $seasonId,
        ]);
    }

    public function actionAjaxSaveTariffs()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $post = Yii::$app->request->post();
        if (isset($post['vehicle_id']) && isset($post['season_id'])) {
            $result = false;

            $vehicleId = $post['vehicle_id'];
            $seasonId = $post['season_id'];
            $from = Yii::$app->request->post('from', '');

            $tariffs = Tariff::find()
                ->where(['vehicle_idVehicle' => $vehicleId, 'season_idSeason' => $seasonId])
                ->orderBy('minDays')
                ->all();
            $originalTariffsIds = array_map(function($tariff) { return $tariff->idTariff; }, $tariffs);

            // Validate that vehicle belongs to user's company
            $vehicle = Vehicle::findOne(['idVehicle' => $vehicleId, 'company_idCompany' => Yii::$app->user->identity->companyId]);
            if (!$vehicle) {
                throw new ForbiddenHttpException('You are not allowed to perform this action.');
            }

            // Validate that season belongs to user's company
            $season = Season::findOne(['idSeason' => $seasonId, 'company_idCompany' => Yii::$app->user->identity->companyId]);
            if (!$season) {
                throw new ForbiddenHttpException('You are not allowed to perform this action.');
            }

            $vehicle_urlCode = $vehicle->urlCode;

            // Process tariff data
            $success = true;
            $processedIds = [];
            $tariffModels = [];
            $month_price = $post['month_price'] ?? '';
            $month_calculate = $post['month_calculate'] ?? 0;
            $transaction = Yii::$app->db->beginTransaction();

            try {
                $newTariffs = $post['Tariff'] ?? [];
                foreach ($newTariffs as $index => $tariffData) {
                    $tariffId = isset($tariffData['idTariff']) ? $tariffData['idTariff'] : null;
                    if ($tariffId && in_array($tariffId, $originalTariffsIds)) {
                        $processedIds[] = $tariffId;
                    }
                }

                $tariffsToDelete = array_diff($originalTariffsIds, $processedIds);
                if (!empty($tariffsToDelete)) {
                    Tariff::deleteAll(['idTariff' => $tariffsToDelete]);
                }

                // Then, save new tariffs
                foreach ($newTariffs as $index => $tariffData) {
                    if (!empty($tariffData['minDays']) && !empty($tariffData['maxDays']) && isset($tariffData['price'])) {
                        $tariffId = isset($tariffData['idTariff']) ? $tariffData['idTariff'] : null;

                        if ($tariffId && in_array($tariffId, $originalTariffsIds)) {
                            // Update existing range
                            $tariff = Tariff::findOne($tariffId);

                        } else {
                            // Create new range
                            $tariff = new Tariff();
                            $tariff->vehicle_idVehicle = $vehicleId;
                            $tariff->season_idSeason = $seasonId;
                        }

                        $tariff->minDays = $tariffData['minDays'];
                        $tariff->maxDays = $tariffData['maxDays'];
                        $tariff->price = $tariffData['price'];

                        if (!$tariff->save()) {
                            $success = false;
                        }

                        $tariffModels[] = $tariff;
                    }
                }

                $monthTariff = TariffMonth::find()->where(['vehicle_idVehicle' => $vehicleId, 'season_idSeason' => $seasonId])
                    ->one();
                if ($monthTariff) {
                    if ($month_price != '') {
                        $monthTariff->price = $month_price;
                        $monthTariff->useForCalculate = $month_calculate;
                        if (!$monthTariff->save()) {
                            $success = false;
                        }
                    } else {
                        $monthTariff->delete();
                    }
                } elseif ($month_price != '') {
                    $monthTariff = new TariffMonth();
                    $monthTariff->vehicle_idVehicle = $vehicleId;
                    $monthTariff->season_idSeason = $seasonId;
                    $monthTariff->price = $month_price;
                    $monthTariff->useForCalculate = $month_calculate;
                    if (!$monthTariff->save()) {
                        $success = false;
                    }
                }

                if ($success) {
                    $transaction->commit();

                    if ($from=='vehicle') {
                        Yii::$app->session->setFlash('success', Yii::t('app', 'Tariffs saved successfully.'));
                    }

                    $result = true;
                } else {
                    $transaction->rollBack();
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
            }

            return [
                'success' => $result,
                'html' => $this->renderAjax('_tariffs_form', [
                    'from' => $from,
                    'vehicle_urlCode' => $vehicle_urlCode,
                    'tariffs' => $tariffModels,
                    'month_price' => $month_price,
                    'month_calculate' => $month_calculate,
                    'selectedVehicleId' => $vehicleId,
                    'selectedSeasonId' => $seasonId
                ])
            ];
        }
    }

    /**
     * Lists all BrandModel models.
     * @return mixed
     */
    public function actionGetVehicles()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $idVehicleType = Yii::$app->request->get('idVehicleType', '');
        $idBrand = Yii::$app->request->get('idBrand', '');
        $idBrandModel = Yii::$app->request->get('idBrandModel', '');

        $types_array = [];
        $vehicles_array = [];
        $brands_array = [];
        $models_array = [];

        $types = VehicleType::find()->all();
        if (count($types)) {
            foreach ($types as $type) {
                $types_array[] =  [
                    'id' => $type->idVehicleType,
                    'name' => $type->getTitle(true)
                ];
            }
        }

        $brands_query = Brand::find()
            ->leftJoin('BrandModel', 'BrandModel.brand_idBrand = Brand.idBrand')
            ->leftJoin('Vehicle', 'Vehicle.brandmodel_idBrandModel = BrandModel.idBrandModel AND Vehicle.company_idCompany=' . Yii::$app->user->getIdentity()->companyId)
            ->andWhere(['is not', 'idVehicle', null]);
        if (!empty($idVehicleType)) {
            $brands_query->andWhere(['BrandModel.vehicletype_idVehicleType'=>$idVehicleType]);
        }

        $brands = $brands_query->orderBy('name ASC')->all();
        if (count($brands)) {
            foreach ($brands as $brand) {
                $brands_array[] =  [
                    'id' => $brand->idBrand,
                    'name' => $brand->name
                ];
            }
        }

        $models_query = BrandModel::find()
            ->leftJoin('Vehicle', 'Vehicle.brandmodel_idBrandModel = idBrandModel AND company_idCompany=' . Yii::$app->user->getIdentity()->companyId)
            ->andWhere(['is not', 'idVehicle', null]);
        if (!empty($idVehicleType)) {
            $models_query->andWhere(['BrandModel.vehicletype_idVehicleType'=>$idVehicleType]);
        }
        if (!empty($idBrand)) {
            $models_query->andWhere(['brand_idBrand'=>$idBrand]);
        }

        $models = $models_query->orderBy('name ASC')->all();
        if (count($models)) {
            foreach ($models as $model) {
                $models_array[] =  [
                    'id' => $model->idBrandModel,
                    'name' => $model->name
                ];
            }
        }

        $vehicles_query = Vehicle::find()->andWhere(['company_idCompany'=>Yii::$app->user->getIdentity()->companyId])->joinWith('brandmodel.brand');
        if (!empty($idVehicleType)) {
            $vehicles_query->andWhere(['BrandModel.vehicletype_idVehicleType'=>$idVehicleType]);
        }
        if (!empty($idBrand)) {
            $vehicles_query->andWhere(['BrandModel.brand_idBrand'=>$idBrand]);
        }
        if (!empty($idBrandModel)) {
            $vehicles_query->andWhere(['Vehicle.brandmodel_idBrandModel'=>$idBrandModel]);
        }

        $vehicles = $vehicles_query->orderBy('Brand.name,BrandModel.name,num')->all();
        if (count($vehicles)) {
            foreach ($vehicles as $vehicle) {
                $vehicles_array[] = [
                    'id' => $vehicle->idVehicle,
                    'name' => $vehicle->getName('full')
                ];
            }
        }

        return ['types'=>$types_array, 'brands'=>$brands_array, 'models'=>$models_array, 'vehicles'=>$vehicles_array];
    }

    /**
     * Lists all BrandModel models.
     * @return mixed
     */
    public function actionGetSeasons()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $seasons_array = [];

        $idVehicle = Yii::$app->request->get('idVehicle', '');
        if (!empty($idVehicle)) {
            $vehicle = Vehicle::find()->andWhere(['company_idCompany'=>Yii::$app->user->getIdentity()->companyId])->andWhere(['Vehicle.idVehicle'=>$idVehicle])->one();

            if ($vehicle) {
                $seasons = Season::find()->andWhere(['company_idCompany' => Yii::$app->user->getIdentity()->companyId])->andWhere(['vehicleType_idVehicleType' => $vehicle->brandmodel->vehicletype_idVehicleType])->all();
                if (count($seasons)) {
                    foreach ($seasons as $season) {
                        $seasons_array[] = [
                            'id' => $season->idSeason,
                            'name' => $season->name
                        ];
                    }
                }
            }
        }

        return ['seasons'=>$seasons_array];
    }

    /**
     * Finds the Tariff model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Tariff the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     * @throws ForbiddenHttpException if the user is not allowed to access this record
     */
    protected function findModel($id)
    {
        $model = Tariff::find()
            ->joinWith('vehicle')
            ->where(['Tariff.idTariff' => $id])
            ->one();

        if ($model === null) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        // Check if the tariff belongs to the user's company
        if ($model->vehicle->company_idCompany != Yii::$app->user->identity->companyId) {
            throw new ForbiddenHttpException('You are not allowed to perform this action.');
        }

        return $model;
    }
}