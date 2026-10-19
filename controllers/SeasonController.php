<?php

namespace app\controllers;

use app\models\SeasonSearch;
use Yii;
use app\models\Season;
use app\models\SeasonRange;
use yii\caching\TagDependency;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * SeasonController implements the CRUD actions for Season model.
 */
class SeasonController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['index', 'create', 'update', 'delete'],
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
     * Lists all Season models.
     *
     * @return string
     */
    public function actionIndex()
    {

        $searchModel = new SeasonSearch();
        $searchModel->company_idCompany=Yii::$app->user->getIdentity()->companyId;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new Season model.
     * If creation is successful, the browser will be redirected to the 'index' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Season();
        $seasonRanges = [new SeasonRange()];

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $rangesData = Yii::$app->request->post('SeasonRange', []);

            $valid = true;
            $seasonRangeModels = [];

            foreach ($rangesData as $data) {
                $range = new SeasonRange();
                $range->vehicleType = $model->vehicletype_idVehicleType;
                $range->season_idSeason = $model->idSeason;
                $range->startDate = $data['startDate'];
                $range->endDate = $data['endDate'];

                if (!$range->validate()) {
                    $valid = false;
                }

                $seasonRangeModels[] = $range;
            }

            // Check for overlapping ranges among the current set
            if ($valid && !empty($seasonRangeModels) && !SeasonRange::validateRange($seasonRangeModels)) {
                $valid = false;
            }

            if ($valid) {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $model->save(false); // Already validated

                    foreach ($seasonRangeModels as $range) {
                        $range->season_idSeason = $model->idSeason;
                        $range->save(false); // Already validated
                    }

                    $transaction->commit();

                    return $this->redirect(['index']);
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', 'Error saving data: ' . $e->getMessage());
                }
            } else {
                $seasonRanges = $seasonRangeModels; // Return validated models with errors
            }
        }

        return $this->render('create', [
            'model' => $model,
            'seasonRanges' => $seasonRanges,
        ]);
    }

    /**
     * Updates an existing Season model.
     * If update is successful, the browser will be redirected to the 'index' page.
     * @param int $id Id Season
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $seasonRanges = $model->getSeasonRanges()->orderBy(['startDate' => SORT_ASC])->all();;
        $originalRangeIds = array_map(function($range) { return $range->idSeasonRange; }, $seasonRanges);

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $rangesData = Yii::$app->request->post('SeasonRange', []);
            $valid = true;
            $seasonRangeModels = [];
            $processedIds = [];

            foreach ($rangesData as $i => $data) {
                // Check if this has an ID (existing range)
                $rangeId = isset($data['idSeasonRange']) ? $data['idSeasonRange'] : null;

                if ($rangeId && in_array($rangeId, $originalRangeIds)) {
                    // Update existing range
                    $range = SeasonRange::findOne($rangeId);
                    $processedIds[] = $rangeId;
                } else {
                    // Create new range
                    $range = new SeasonRange();
                }

                $range->vehicleType = $model->vehicletype_idVehicleType;
                $range->season_idSeason = $model->idSeason;
                $range->startDate = $data['startDate'];
                $range->endDate = $data['endDate'];

                if (!$range->validate()) {
                    $valid = false;
                }

                $seasonRangeModels[] = $range;
            }

            // Check for overlapping ranges among the current set
            if ($valid && !empty($seasonRangeModels) && !SeasonRange::validateRange($seasonRangeModels)) {
                $valid = false;
            }

            // Determine which ranges should be deleted
            $rangesToDelete = array_diff($originalRangeIds, $processedIds);

            if ($valid) {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $model->save(false); // Already validated

                    // Save ranges
                    foreach ($seasonRangeModels as $range) {
                        $range->season_idSeason = $model->idSeason;
                        $range->save(false); // Already validated
                    }

                    // Remove ranges that are no longer present
                    if (!empty($rangesToDelete)) {
                        SeasonRange::deleteAll(['idSeasonRange' => $rangesToDelete]);
                    }

                    $transaction->commit();

                    return $this->redirect(['index']);
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', 'Error updating data: ' . $e->getMessage());
                }
            } else {
                $seasonRanges = $seasonRangeModels; // Return validated models with errors
            }
        }

        return $this->render('update', [
            'model' => $model,
            'seasonRanges' => $seasonRanges,
        ]);
    }

    /**
     * Deletes an existing Season model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id Id Season
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        // Check if this model can be deleted
        if (!$model->canDelete()) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'This season cannot be deleted because it is in use.'));
            return $this->redirect(['index']);
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Delete related ranges first
            SeasonRange::deleteAll(['season_idSeason' => $id]);
            // Then delete the season
            $model->delete();

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Error deleting data: ' . $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    /**
     * Finds the Season model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id Id Season
     * @return Season the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Season::findOne($id)) !== null) {
            if ($model->company_idCompany!=Yii::$app->user->getIdentity()->companyId) {
                throw new ForbiddenHttpException('You are not allowed to perform this action.');
            }
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}