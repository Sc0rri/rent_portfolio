<?php

namespace app\components\api;

use yii\base\Module;

/**
 * Base module for the REST API.
 */
class Api extends Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\components\api\controllers';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
    }
}