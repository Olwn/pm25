<?php

namespace app\models;
use Yii;

class User extends \yii\db\ActiveRecord
{
    
    public static function tableName()
    {
        return 'user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'email', 'firstname', 'lastname', 'sex', 'phone', 'password'], 'required'],
            [['name'], 'unique'],
        ];
    }

    public function attributeLabels()  
    {  
        return array(  
            'id' => 'id',  
        );  
    }  

 
}
