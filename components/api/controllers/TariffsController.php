<?php
namespace app\components\api\controllers;

use app\components\api\components\DefaultController;
use app\components\api\components\BearerAuth;
use app\components\api\components\Entity;
use app\models\Vehicle;
use app\models\TariffMonthSearch;
use app\models\TariffSearch;
use Yii;
use yii\filters\AccessControl;

/**
 * @inheritdoc
 */
class TariffsController extends DefaultController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => BearerAuth::className()
        ];

        $behaviors['access'] = [
            'class' => AccessControl::className(),
            'rules' => [
                [
                    'actions' => [
                        'daily',
                        'month'
                    ],
                    'allow' => true,
                    'roles' => ['manager', 'owner'],
                ],
                [
                    'actions' => ['daily', 'month'],
                    'allow'   => true,
                    'roles'   => ['api_user'],
                    'verbs'   => ['GET'],
                ]
            ],
        ];

        return $behaviors;
    }

    /**
     * Get daily tariffs
     *
     */
    public function actionDaily()
    {
        switch (Yii::$app->request->getMethod()) {
            case 'GET':
                $request = [];

                $searchByVehicle = false;
                $vehicleCode = Yii::$app->request->get('vehicleCode', false);
                if ($vehicleCode) {
                    if (($vehicle = Vehicle::findByUrlCode($vehicleCode)) !== null) {
                        if ($vehicle->company_idCompany == Yii::$app->user->getIdentity()->companyId) {
                            $request['vehicle_idVehicle'] = $vehicle->idVehicle;
                            $searchByVehicle = true;
                        }
                    }
                }

                if (!$searchByVehicle) {
                    $request['company_idCompany'] = Yii::$app->user->getIdentity()->companyId;
                }

                $season = Yii::$app->request->get('season', false);
                if ($season) {
                    $request['season_idSeason'] = $season;
                }

                $searchModel = new TariffSearch();
                $dataProvider = $searchModel->search(['TariffSearch' => $request]);

                $resp = [
                    'pagination' => null,
                    'tariffs' => []
                ];

                $pageSize = Yii::$app->request->get('pageSize', false);
                if ($pageSize) {
                    $total = $dataProvider->getTotalCount();
                    $page = (int)Yii::$app->request->get('page', 1);

                    $dataProvider->pagination->pageSize = $pageSize;
                    $dataProvider->pagination->page = $page - 1;

                    $resp['pagination'] = [
                        'perPage'=> (int)$pageSize,
                        'total' => $total,
                        'page'=> $page,
                        'totalPages'=> ceil($total/$pageSize),
                    ];
                } else {
                    $dataProvider->pagination->pageSize = false;
                }

                foreach ($dataProvider->getModels() as $one_tariff) {
                    $resp['tariffs'][] = Entity::tariffDaily($one_tariff);
                }

                return Entity::apiResponse($resp);
                break;
            default:
                return Entity::apiError(Yii::t('app', 'Not implemented'), 501);
                break;
        }
    }

    /**
     * Get monthly tariffs
     *
     */
    public function actionMonth()
    {
        switch (Yii::$app->request->getMethod()) {
            case 'GET':
                $request = [];

                $searchByVehicle = false;
                $vehicleCode = Yii::$app->request->get('vehicleCode', false);
                if ($vehicleCode) {
                    if (($vehicle = Vehicle::findByUrlCode($vehicleCode)) !== null) {
                        if ($vehicle->company_idCompany == Yii::$app->user->getIdentity()->companyId) {
                            $request['vehicle_idVehicle'] = $vehicle->idVehicle;
                            $searchByVehicle = true;
                        }
                    }
                }

                if (!$searchByVehicle) {
                    $request['company_idCompany'] = Yii::$app->user->getIdentity()->companyId;
                }

                $season = Yii::$app->request->get('season', false);
                if ($season) {
                    $request['season_idSeason'] = $season;
                }

                $searchModel = new TariffMonthSearch();
                $dataProvider = $searchModel->search(['TariffMonthSearch' => $request]);

                $resp = [
                    'pagination' => null,
                    'tariffs' => []
                ];

                $pageSize = Yii::$app->request->get('pageSize', false);
                if ($pageSize) {
                    $total = $dataProvider->getTotalCount();
                    $page = (int)Yii::$app->request->get('page', 1);

                    $dataProvider->pagination->pageSize = $pageSize;
                    $dataProvider->pagination->page = $page - 1;

                    $resp['pagination'] = [
                        'perPage'=> (int)$pageSize,
                        'total' => $total,
                        'page'=> $page,
                        'totalPages'=> ceil($total/$pageSize),
                    ];
                } else {
                    $dataProvider->pagination->pageSize = false;
                }

                foreach ($dataProvider->getModels() as $one_tariff) {
                    $resp['tariffs'][] = Entity::tariffMonth($one_tariff);
                }

                return Entity::apiResponse($resp);
                break;
            default:
                return Entity::apiError(Yii::t('app', 'Not implemented'), 501);
                break;
        }
    }
}
