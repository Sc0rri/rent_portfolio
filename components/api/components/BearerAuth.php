<?php

namespace app\components\api\components;

use Yii;
use app\models\User;
use yii\filters\auth\HttpBearerAuth;

class BearerAuth extends HttpBearerAuth
{
    /**
     * @inheritdoc
     */
    public function handleFailure($response)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->content = json_encode(Entity::apiError(Yii::t('app', 'Authorization required'), 401));
    }

    /**
     * @inheritdoc
     */
    public function authenticate($user, $request, $response)
    {
        $authHeader = $request->getHeaders()->get($this->header);
        if ($authHeader !== null && preg_match($this->pattern, $authHeader, $matches)) {
            $identity = User::findByApiToken($matches[1]);
            if ($identity !== null) {
                $user->setIdentity($identity);
                return $identity;
            }
        }

        if ($authHeader !== null) {
            $this->handleFailure($response);
        }

        return null;
    }
}