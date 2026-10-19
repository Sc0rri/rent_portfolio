<?php
namespace app\components\api\controllers;

use app\components\api\components\DefaultController;
use app\components\api\components\BearerAuth;
use app\components\api\components\Entity;
use app\models\Vehicle;
use app\models\SeasonSearch;
use app\models\TariffMonthSearch;
use app\models\TariffSearch;
use Yii;
use yii\filters\AccessControl;

/**
 * @inheritdoc
 */
class SeasonsController extends DefaultController
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
                        'index',
                    ],
                    'allow' => true,
                    'roles' => ['manager', 'owner'],
                ],
                [
                    'actions' => ['index'],
                    'allow'   => true,
                    'roles'   => ['api_user'],
                    'verbs'   => ['GET'],
                ]
            ],
        ];

        return $behaviors;
    }

    /**
     * Get seasons
     *
     */
    public function actionIndex()
    {
        switch (Yii::$app->request->getMethod()) {
            case 'GET':
                $request = [];

                $searchModel = new SeasonSearch();
                $searchModel->company_idCompany=Yii::$app->user->getIdentity()->companyId;
                $dataProvider = $searchModel->search([]);

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


                if ($dataProvider->count) {
                    $currentMonth = date('m');
                    $currentDay   = date('d');
                    if ($currentMonth == 2 && $currentDay == 29) {
                        $currentDay = 28;
                    }
                    $currentDate = $currentMonth.'-'.$currentDay;

                    foreach ($dataProvider->getModels() as $one_season) {
                        $resp['seasons'][] = Entity::season($one_season, $currentDate);
                    }
                }

                return Entity::apiResponse($resp);
                break;
            default:
                return Entity::apiError(Yii::t('app', 'Not implemented'), 501);
                break;
        }
    }
}
