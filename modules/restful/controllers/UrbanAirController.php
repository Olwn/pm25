<?php

namespace app\modules\restful\controllers;

use Yii;
use yii\data\ActiveDataProvider;

class UrbanAirController extends \yii\rest\ActiveController
{
    public $modelClass = 'app\models\UrbanAir';

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

    public function actionSearch()
    {
        $conditions = Yii::$app->request->get();
        $url = "http://urbanair.msra.cn/U_Air/SearchGeoPoint?Culture=zh-CN&Standard=0";
        $result = file_get_contents($url . "&Longitude=" . $conditions['longitude'] . "&Latitude=" . $conditions['latitude']);
        $obj = json_decode($result);
        if(isset($obj->PM25))
        {
            $obj->source = '1';
            return $obj;
        }
            
        $query = (new\yii\db\Query())
                ->select('*')
                ->from('area_position')
                ->Where(['between', 'Longitude', $conditions['longitude']-1, $conditions['longitude']+1])
                ->AndWhere(['between', 'Latitude', $conditions['latitude']-1, $conditions['latitude']+1]);
        $models = $query->all();
        $min_diff = 999999;
        $station_code;
        foreach ($models as $model) {
            $area = abs(($model['longitude'] - $conditions['longitude'])*($model['latitude'] - $conditions['latitude']));
            if($area < $min_diff)
            {
                $min_diff = $area;
                $station_code = $model['station_code'];
            }
        }
        $query = (new\yii\db\Query())
                ->select('*')
                ->from('air_quality')
                ->where(['=', 'station_code', $station_code])
                ->orderBy(['time_point' => SORT_DESC])
                ->limit(1);
        $result = $query->all();
        if(count($result) > 0)
        {
            $result[0]['PM25']=$result[0]['pm2_5'];
            $result[0]['source'] = '2';
            unset($result[0]['pm2_5']);
        }
        return $result;
    }

    public function prepareDataProvider()
    {
        $conditions = Yii::$app->request->get();
        //echo $conditions['time_point'];
		if (!isset(/*$conditions['city'],*/ $conditions['latitude'], $conditions['longitude'], $conditions['time_point']))
		{
			return null;
		}
        return $this->queryWithConditions($conditions);
    }

    public function queryWithConditions($conditions)
    {
        $query = (new \yii\db\Query())
            ->select('*')
            ->from('urban_air');
        

        //$query->Where(['=','city', $conditions['city']]);
        $query->Where(['=','time_point', $conditions['time_point']]);
        $lat = (double)$conditions['latitude'];
        $lon = (double)$conditions['longitude'];
        $query->AndWhere(['between', 'latitude', $lat-0.05, $lat+0.05]);
        $query->AndWhere(['between', 'longitude', $lon-0.05, $lon+0.05]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
        ]);

        $model = new $this->modelClass;
        $models = $dataProvider->getModels();
        if(count($models) == 0)
            return;
        $min_diff = 99999;
        
        $index = 0;
        for($x=0; $x<count($models); $x++) {
            # code...
            $model = $models[$x];
            $distance = abs(($model['longitude'] - $lon) * ($model['latitude'] - $lat));
            if($distance < $min_diff)
            {
                $index = $x;
                $min_diff = $distance;
            }
        }

        $dataProvider->setModels($models[$index]);

        return $dataProvider;
    }
}