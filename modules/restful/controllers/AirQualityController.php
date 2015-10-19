<?php

namespace app\modules\restful\controllers;

use Yii;
use yii\data\ActiveDataProvider;

class AirQualityController extends \yii\rest\ActiveController
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
        //$actions['create']['run'] = [$this, 'run1'];

        return $actions;
    }

    public function prepareDataProvider()
    {
        $conditions = Yii::$app->request->get();
        //echo $conditions['time_point'];
		if (!isset($conditions['area']))
		{
			return null;
		}
        return $this->queryWithConditions($conditions);
    }

    public function queryWithConditions($conditions)
    {
        $query = (new \yii\db\Query())
            ->select('*')
            ->from('air_quality');
            

        if(!isset($conditions['time_point']))
        {
            $sql="select time_point from air_quality where id=( select MAX(id) from air_quality)";  
            //a subquery to get the latest time
            $connection = Yii::$app->db;
            $command = $connection->createCommand($sql);    
            $result = $command->queryScalar();

            // get data of latest time
            $query->where(['=', 'time_point', $result]);    
        }
        else
        {
            if(strlen($conditions['time_point']) < 15 )
                $query->where(['=', "DATE_FORMAT(time_point, '%Y-%m-%d')", $conditions['time_point']]);
            unset($conditions['time_point']);
        }

        $query->andWhere($conditions);
        $dataProvider = new ActiveDataProvider([
            /*'query' => $model::find()->where($conditions)->orderBy('time_point DESC')->limit(200),*/
            'query' => $query,
            'pagination' => false,
        ]);

        $model = new $this->modelClass;
        $models = $dataProvider->getModels();
        $dataProvider->setModels($models);

        return $dataProvider;
    }

    private function todayAndPastDays($models)
    {
        $timeZone = new \DateTimeZone('Asia/Shanghai');
        $today = $day = new \DateTime("now", $timeZone);
        $format = 'Y-m-d';
        $formatFull = 'Y-m-d H:i:s';
        $result = [[]];
        foreach ($models as $model)
        {
            $time_point = new \DateTime($model['time_point'], $timeZone);
            if ($today->format($format) == $time_point->format($format))
            {
                if ($today->format($formatFull) != $time_point->format($formatFull))
                {
                    array_push($result[0], $model);
                    $today = new \DateTime($model['time_point'], $timeZone);
                }
            }
            else
            {
                if ($day->format($format) != $time_point->format($format))
                {
                    array_push($result, $model);
                    $day = new \DateTime($model['time_point'], $timeZone);
                }
            }
        }
        return $result;
    }
}
