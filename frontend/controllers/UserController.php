<?php

namespace frontend\controllers;

use common\models\User;
use yii\rest\ActiveController;

class UserController extends ActiveController
{
    public function behaviors()
    {
        return [
            'contentNegotiator' => [
                'class' => \yii\filters\ContentNegotiator::class,
                'formatParam' => '_format',
                'formats' => [
                    'xml' => \yii\web\Response::FORMAT_XML,
                    'application/json' => \yii\web\Response::FORMAT_JSON
                ]
            ]
        ];
    }

    public $modelClass = User::class;

}