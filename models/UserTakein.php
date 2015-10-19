<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "user_takein".
 *
 * @property string $id
 * @property string $device_token
 * @property string $value
 * @property string $time_point
 */
class UserTakein extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_takein';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['device_token', 'value', 'time_point'], 'required'],
            [['device_token', 'value'], 'string'],
            [['time_point'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'id',
            'device_token' => 'device token',
            'value' => 'value of taken in encrypted',
            'time_point' => 'time point',
        ];
    }
}
