<?php

namespace tests\codeception\unit\models;

use Yii;
use yii\codeception\TestCase;
use app\models\AirQuality;
use Codeception\Specify;

class AirQualityTest extends TestCase
{
    use Specify;
    protected function setUp()
    {
        parent::setup();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }
    
    public function testLoad()
    {
        $model = new AirQuality();
        $value = [
            'aqi' => 19,
            'area' => '神奇的香格里拉',
            'position_name' => '山里',
            'station_code' => '12345',
            'pm2_5' => 50,
            'pm2_5_24h' => 20,
            'primary_pollutant' => '很多污染物',
            'quality'=>'优',
            'time_point' => '2015-06-02T18:00:00Z',
            ];
        $model->load(['AirQuality' => $value]);
        $attributes = $model->attributes;
        $this->specify('load succedded', function() use($value, $attributes) {
            $this->assertEquals($value['aqi'], $attributes['aqi']);
        });

        $value['primary_pollutant'] = null;
        $value['quality'] = null;

        $model->load(['AirQuality' => $value]);
        $attributes = $model->attributes;
        $this->specify('load succedded', function() use($value, $attributes) {
            $this->assertEquals($value['aqi'], $attributes['aqi']);
        });
    }
}


