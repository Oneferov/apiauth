<?php

namespace frontend\models;

use Yii;

class CheckCode extends \yii\db\ActiveRecord
{
    
    public static function tableName()
    {
        return 'check_codes';
    }

  
    public function rules()
    {
        return [
            [['created_at', 'updated_at', 'code'], 'integer'],
            [['username', 'option'], 'string', 'max' => 255],
        ];
    }

    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username]);
    }
}
