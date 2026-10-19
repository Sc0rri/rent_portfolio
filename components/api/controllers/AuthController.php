<?php
namespace app\components\api\controllers;

use Yii;
use app\models\User;
use app\components\api\components\DefaultController;
use app\components\api\components\RateLimiter;
use app\components\api\components\Entity;

/**
 * @inheritdoc
 */
class AuthController extends DefaultController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['rateLimiter'] = [
            'class' => RateLimiter::className()
        ];
        return $behaviors;
    }

    /**
     * Obtain an API token
     */
    public function actionIndex()
    {
        $params = Yii::$app->request->post();
        if (empty($params['login']) || empty($params['password'])) {
            if (empty($params['login']) && empty($params['password'])) {
                return Entity::apiResponse(null, ['login' => [Yii::t('app', 'Login is required')], 'password' => [Yii::t('app', 'Password is required')]]);
            } elseif (empty($params['login'])) {
                return Entity::apiResponse(null, ['login' => [Yii::t('app', 'Login is required')]]);
            } elseif (empty($params['password'])) {
                return Entity::apiResponse(null, ['password' => [Yii::t('app', 'Password is required')]]);
            }
        }

        $user = User::findByUsername($params['login']);
        if ($user && $user->validatePassword($params['password']) && $user->userRole && !in_array($user->userRole->name, ['admin', 'agent'])) {
            $user->generateApiToken();
            $user->save();

            return Entity::apiResponse(['token'=>$user->apiToken, 'user'=>Entity::user($user)]);
        } else {
            return Entity::apiResponse(null, ['password'=>[Yii::t('app', 'Login and password do not match any existing user')]], 401);
        }
    }
}