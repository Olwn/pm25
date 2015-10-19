<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "city_air".
 *
 * @property string $id
 * @property integer $aqi
 * @property string $area
 * @property integer $pm2_5
 * @property integer $pm2_5_24h
 * @property string $primary_pollutant
 * @property string $quality
 * @property string $time_point
 */
class CityAir extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'city_air';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['aqi', 'area', 'pm2_5', 'pm2_5_24h', 'time_point'], 'required'],
            [['aqi', 'pm2_5', 'pm2_5_24h'], 'integer'],
            [['area'], 'string'],
            [['time_point'], 'safe'],
            [['primary_pollutant'], 'string', 'max' => 100],
            [['quality'], 'string', 'max' => 8]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'id number',
            'aqi' => 'air quality index',
            'area' => 'city name',
            'pm2_5' => 'pm 2.5 value',
            'pm2_5_24h' => 'pm 2.5 24h value',
            'primary_pollutant' => 'primary pollutant',
            'quality' => 'quality',
            'time_point' => 'time point',
        ];
    }

    /**
     * inherite load() function
     */
    public function load($data, $formName = null)
    {
        parent::load($data, $formName);
        
        if ($this->attributes['time_point'])
        {
            $dateTime = \DateTime::createFromFormat("Y-m-d?H:i:s?", $this->attributes['time_point']);
            $this->setAttributes(['time_point' => 
                $dateTime->format('Y-m-d H:i:s')]);
        }

        if ( null == $this->attributes['primary_pollutant'] )
        {
            $this->setAttributes(['primary_pollutant' => '']);
        }

        if ( null == $this->attributes['quality'] )
        {
            $this->setAttributes(['quality' => '']);
        }
    }
}
