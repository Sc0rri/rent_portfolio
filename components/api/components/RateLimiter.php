<?php

namespace app\components\api\components;

use app\models\User;
use Yii;
use yii\filters\RateLimitInterface;

/**
 * Rate limiter that resolves the user by the `login` request parameter,
 * so the limit can be applied before the identity is available.
 */
class RateLimiter extends \yii\filters\RateLimiter
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if ($this->user === null && ($login = Yii::$app->request->post('login', false))) {
            $this->user = User::findByUsername($login);
        }

        if ($this->user instanceof RateLimitInterface) {
            Yii::trace('Check rate limit', __METHOD__);
            $this->checkRateLimit($this->user, $this->request, $this->response, $action);
        } elseif ($this->user) {
            Yii::info('Rate limit skipped: "user" does not implement RateLimitInterface.', __METHOD__);
        } else {
            Yii::info('Rate limit skipped: user not logged in.', __METHOD__);
        }

        return true;
    }
}