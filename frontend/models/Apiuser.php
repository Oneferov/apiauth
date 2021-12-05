<?php

namespace frontend\models;

use Yii;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "robots".
 *
 * @property int $id
 * @property string $username
 * @property string $password_hash
 * @property string|null $access_token
 * @property int $created_at
 * @property int $updated_at
 */
class Apiuser extends \yii\db\ActiveRecord 
{

    protected static function getSecretKey()
    {
        return 'someSecretKey';
    }

    // And this one if you wish
    protected static function getHeaderToken()
    {
        return [];
    }
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'apiusers';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'updated_at'], 'integer'],
            [['username', 'password_hash', 'access_token'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Username ID',
            'password_hash' => 'Password_hash',
            'access_token' => 'Access_token',
            'created_at' => 'Created_at',
            'updated_at' => 'Updated_at',
        ];
    }

    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username]);
    }

    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['access_token' => $token]);
    }

}
