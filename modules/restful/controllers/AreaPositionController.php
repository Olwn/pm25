<?php

namespace app\modules\restful\controllers;

use Yii;
use yii\data\ActiveDataProvider;

class AreaPositionController extends \yii\rest\ActiveController
{
    public $modelClass = 'app\models\AirQuality';
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actions()
    {
        $actions = parent::actions();

        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];

        return $actions;
    }

    public function prepareDataProvider()
    {
        $conditions = Yii::$app->request->get();
        return $this->queryWithConditions($conditions);
    }

    public function queryWithConditions($conditions)
    {
        $query = (new \yii\db\Query())
            ->select(['area', 'position_name', 'latitude', 'longitude', 'alias'])
            ->from('area_position')
            ->where($conditions);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' =>false,
            ]);

        $models = $dataProvider->getModels();

        // modify models
        $models = $this->foldAreas($models);

        $dataProvider->setModels($models);

        return $dataProvider;
    }

    private function foldAreas($models)
    {
        $result = [];
        foreach ($models as $model)
        {
            $area = $model['area'];
            $position_name = $model['position_name'];
            $temp = $model;
            unset($temp['area']);
            if (array_key_exists($area, $result))
            {
                array_push($result[$area], $temp);
            }
            else 
            {
                $result[$area] = [$temp];
            }
        }
        return $result;
    }
}
