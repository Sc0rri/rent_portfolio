<?php

namespace app\components\api\components;

use Yii;
use yii\base\Controller;
use yii\filters\ContentNegotiator;
use yii\web\Response;

/**
 * Base API controller: negotiates the JSON response format and the language,
 * and applies the authenticated user's time zone before each action.
 */
class DefaultController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['ContentNegotiator'] = [
            'class' => ContentNegotiator::className(),
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
            'languageParam' => 'lang',
            'languages' => ['en', 'ua', 'es'],
        ];
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if (Yii::$app->user && Yii::$app->user->identity) {
            Yii::$app->user->identity->setTimeZone();
        }

        return true;
    }
}