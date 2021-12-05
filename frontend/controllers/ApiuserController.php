<?php

namespace frontend\controllers;

use frontend\models\Apiuser;
use frontend\models\Checkcode;

use yii\rest\ActiveController;

use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\HttpBasicAuth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use yii\helpers\Url;
use Yii;

class ApiuserController extends ActiveController
{
    public function behaviors()
    {
        return [
            'contentNegotiator' => [
                'class' => \yii\filters\ContentNegotiator::class,
                'formatParam' => '_format',
                'formats' => [
                    'application/json' => \yii\web\Response::FORMAT_JSON,
                ]
            ],
            'authenticator' => [
                'class' => CompositeAuth::class,
                'authMethods' => [
                    HttpBearerAuth::class,
                    HttpBasicAuth::class,
                ]
            ],
                
        ];
    }

    public function init() 
    {
        parent::init();
        \Yii::$app->user->enableSession = false;
    }

    public $enableCsrfValidation = false;

    public $modelClass = Apiuser::class;

    public function beforeAction($action)
    {
        $unf = Yii::$app->user->identity;
        $res = [
            'res' => $unf
        ];
        return $this->asJson($res);
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionRegistration()
    {
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $login = $request->getBodyParam("login");
        $code = $request->getBodyParam("code");

        if ($login) {

            if ($code) {
                $password = $request->getBodyParam("password");
                $headers = Yii::$app->request->headers;

                if (!$password) {
                    return $this->getError("Введите пароль");
                }

                $checked = Checkcode::findByUsername($login);

                if ($checked) {
                    if ($checked->code == $code && $checked->option == 'reg') {
                        if (!Apiuser::findByUsername($login)) {

                            $new_user = new Apiuser;
                            $new_user->username = $login;
                            $new_user->created_at = time();
                            $new_user->updated_at = time();
                            $new_user->save();

                            $new_user_with_pass = Apiuser::findByUsername($login);
                            
                            $key = "example_key";
                            $payload = array(
                                "user-agent" => $headers->get('user-agent'),
                                "user_id" => $new_user_with_pass->id,
                                "time" => time()
                            );
                            $jwt = JWT::encode($payload, $key, 'HS256');
                            
                            $new_user_with_pass->access_token = $jwt;
                            $new_user_with_pass->setPassword($password);
                            $new_user_with_pass->save();

                            $checked->delete();

                        } else {
                            return $this->getError('Пользователь с таким именем уже зарегистрирован');
                            
                        }
                    } else {
                        return $this->getError('Неверный код');
                    }
                } else {
                    return $this->getError('Неверное имя пользователя');
                }

                $headers = Yii::$app->response->headers;
                $headers->set('Authorization', $jwt);

                $response = Yii::$app->response;
                $response->format = \yii\web\Response::FORMAT_JSON;
                $response->data = [
                    "status" => 1,
                    "data" => [
                        "token" => $jwt
                    ]
                ];

                \Yii::$app->response->redirect('http://localhost:20080/api/user/auth/login', 200)->send();
                
            } else {
                $this->getCode('reg');
            }
        } else {
            return $this->getError("Необходимо ввести логин");
        }
    }

    public function actionAuthorization() 
    {
        $request = Yii::$app->request;
        $login = $request->getBodyParam("login");
        $password = $request->getBodyParam("password");

        $headers = Yii::$app->request->headers;

        $autorization = $headers->get('authorization');
        $user_agent = $headers->get('user-agent');

        $new_user_with_pass = Apiuser::findByUsername($login);

        $key = "example_key";
        $decoded = JWT::decode($autorization, new Key($key, 'HS256'));
        $decoded_array = (array) $decoded;

        if ($login) {
            if ($password) {
                if ($new_user_with_pass) {
                    if ($new_user_with_pass->validatePassword($password)) {
                        if ($decoded_array['user-agent'] == $user_agent && $decoded_array['user_id'] == $new_user_with_pass->id) {
                            $payload = array(
                                "user-agent" => $user_agent,
                                "user_id" => $new_user_with_pass->id,
                                "time" => time()
                            );
                            $new_jwt = JWT::encode($payload, $key, 'HS256');
                            $new_user_with_pass->access_token = $new_jwt;
                            $new_user_with_pass->save();
                        } else {
                            $resp = [
                                "name" => "Unauthorized",
                                "message" => "Your request was made with invalid credentials.",
                                "code" => 0,
                                "status" => 401
                            ];
                            return $resp;
                        }
                    } else {
                        return $this->getError('Неправильный пароль');
                    }
                } else {
                    return $this->getError('Пользователь с таким логином не найден');
                }
            } else {
                return $this->getError('Отсутствует пароль');
            }
        } else {
            return $this->getError('Введите имя пользователя');
        }

        return [
            "status" => 1,
            "data" => [
                "token" => $new_jwt
            ]
        ];
    }  

    public function getCode($option)
    {
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $login = $request->getBodyParam("login");

        $new_login = str_replace(" ", '', $login);

        if (strlen($new_login) == 11 && (mb_substr($new_login, 0, 1) == '8') || (mb_substr($new_login, 0, 2) == '+7')) {
            $code = random_int(100000, 999999);
            $check = new Checkcode;
            $check->username = $new_login;
            $check->code = $code;
            $check->option = $option;
            $check->created_at = time();
            $check->updated_at = time();
            $check->save();

            $cod = [
                "status" => 1,
                "data" => [
                    "code" => $code
                ]
            ];

            return $this->asJson($cod);
        } else {
            return $this->getError('Введен некорректный номер');
        }
    }

    public function actionRecovery()
    {
        $request = Yii::$app->request;
        $login = $request->getBodyParam("login");
        $code = $request->getBodyParam("code");

        if ($login) {

            if ($code) {

                $password = $request->getBodyParam("password");
                $headers = Yii::$app->request->headers;

                if (!$password) {
                    return $this->getError('Введите новый пароль');
                }

                $checked = Checkcode::findByUsername($login);

                if ($checked) {
                    if ($checked->code == $code && $checked->option == 'rec') {
                        $new_user_with_pass = Apiuser::findByUsername($login);
                        if ($new_user_with_pass) {
                            
                            $key = "example_key";
                            $payload = array(
                                "user-agent" => $headers->get('user-agent'),
                                "user_id" => $new_user_with_pass->id,
                                "time" => time()
                            );

                            $jwt = JWT::encode($payload, $key, 'HS256');
                            
                            $new_user_with_pass->access_token = $jwt;
                            $new_user_with_pass->setPassword($password);
                            $new_user_with_pass->save();

                            $checked->delete();

                        } else {
                            return $this->getError('Пользователь еще не зарегистрирован');
                        }
                    } else {
                        return $this->getError('Неверный код');
                    }
                } else {
                    return $this->getError('Неверное имя пользователя');
                }

                $headers = Yii::$app->response->headers;
                $headers->set('Authorization', $jwt);

                $response = Yii::$app->response;
                $response->format = \yii\web\Response::FORMAT_JSON;
                $response->data = [
                    "status" => 1,
                    "data" => [
                        "token" => $jwt
                    ]
                ];

                \Yii::$app->response->redirect('http://localhost:20080/api/user/auth/login', 200)->send();

            } else {
                $this->getCode('rec');
            }
        } else {
            return $this->getError("Введите логин");
        }
    }

    public function getError($message)
    {
        return [
            "status" => -1,
                "message" => [
                    $message
                ]
        ];
    }
}