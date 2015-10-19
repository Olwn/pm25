<?php

namespace tests\codeception\unit\models;

use Yii;
use yii\codeception\TestCase;
use app\models\AreaPosition;
use Codeception\Specify;

class AreaPositionTest extends TestCase
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
        $model = new AreaPosition();
        $value = [
            'area' => '神奇的香格里拉',
            'position_name' => '山里',
            'latitude'=>1234.81239,
            'longtitude' => 1239.12,
            ];
        $model->load(['AreaPosition' => $value]);
        $attributes = $model->attributes;
        $this->specify('load succedded', function() use($value, $attributes) {
            $this->assertEquals($value['area'], $attributes['area']);
        });

    }
}


