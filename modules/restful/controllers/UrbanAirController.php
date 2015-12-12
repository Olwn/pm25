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

    public function actionLatestDays()
    {
        $result = array(
            'status' => 1,
            'data' => ''
            );
        $conditions = Yii::$app->request->get();
        $station_code = $this->queryStationCode($conditions['longitude'], $conditions['latitude']);
        if(is_null($station_code))
        {
            $result['status'] = 2;
            return $result;
        }
        $connection = Yii::$app->db;  
        //$sql="SELECT * FROM `express_template`  WHERE `ec_id`=$ec_id";  
        $sql = "select avg(aqi) as AQI, avg(pm2_5) as PM25, date_format(time_point, '%Y-%m-%d') as Date
                 from air_quality where station_code ='" . $station_code . "'group by date_format(time_point, '%Y-%m-%d') 
                 order by time_point desc limit " . $conditions['days'];
        $command = $connection->createCommand($sql);    
        $result['data'] = $command->queryAll();   
        return $result;
    }
    public function actionSearchHistory()
    {
        $results = array(
            'status' => 1,
            'data' => ''
            );
        $conditions = Yii::$app->request->get();
        $date = $conditions['time_point'];
        if(!isset($conditions['use_station']) || $conditions['use_station'] != '1')
        {
            for ($i=0; $i < 24; $i++) 
            { 
                $time_point = $date. ' ';
                if($i < 10)
                    $time_point = $time_point . '0' . strval($i);
                else
                    $time_point = $time_point . strval($i);
                $time_point = $time_point . ':00:00';
                $conditions['time_point'] = $time_point;
                $result[$i] = $this->queryUrbanAirDatabase($conditions);
                $results['data'] = $result;

                if(count($result) == 24)
                    return $results;    
            }
        }
        
        $station_code = $this->queryStationCode($conditions['longitude'], $conditions['latitude']);
        $query = (new\yii\db\Query())
                ->select('*')
                ->from('air_quality')
                ->where(['=', 'station_code', $station_code]);
        $query->AndWhere(['=', "date_format(time_point, '%Y-%m-%d')", $date]);
        
        $result = $query->all();
        $results['status'] = 2;
        $results['data'] = $result;
        return $results;

    }
    public function actionSearch()
    {
        $conditions = Yii::$app->request->get();
        if(isset($conditions['time_point']))
            return $this->searchPoint($conditions);
        $url = "http://urbanair.msra.cn/U_Air/SearchGeoPoint?Culture=zh-CN&Standard=0";
        $result = file_get_contents($url . "&Longitude=" . $conditions['longitude'] . "&Latitude=" . $conditions['latitude']);
        $obj = json_decode($result);

        if(isset($conditions['use_station']) && $conditions['use_station']=='1')
            return $this->queryStationData($conditions, NULL, 1);
        if(isset($obj->PM25))
        {
            $obj->status = '1';
            return $obj;
        }
        
    }

    public function searchPoint()
    {
        $conditions = Yii::$app->request->get();
        //echo $conditions['time_point'];
		if (!isset(/*$conditions['city'],*/ $conditions['latitude'], $conditions['longitude'], $conditions['time_point']))
		{
			return null;
		}

        if(isset($conditions['use_station']) && $conditions['use_station']=='1')
            return $this->queryStationData($conditions, NULL, 1);
        else
        {
            $result = $this->queryUrbanAirDatabase($conditions);
            if($result != NULL)
                return $result;
            else
                return $this->queryStationData($conditions, NULL, 1);
        }
        
    }

    public function queryUrbanAirDatabase($conditions)
    {
        $query = (new \yii\db\Query())
            ->select('*')
            ->from('urban_air')
            ->Where(['=','time_point', $conditions['time_point']]);
        
        $lat = (double)$conditions['latitude'];
        $lon = (double)$conditions['longitude'];
        $query->AndWhere(['between', 'latitude', $lat-0.05, $lat+0.05]);
        $query->AndWhere(['between', 'longitude', $lon-0.05, $lon+0.05]);
        /*
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
        ]);
        */
        $model = new $this->modelClass;
        $models = $query->all();
        if(count($models) == 0)
            return NULL;

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
        $models[$index]['status'] = 1;
        return $models[$index];
    }
    //mode=1 means querying by time, while mode=0 query by date
    public function queryStationData($conditions, $time, $mode)
    {
        $station_code = $this->queryStationCode($conditions['longitude'], $conditions['latitude']);
        $query = (new\yii\db\Query())
                ->select('*')
                ->from('air_quality')
                ->where(['=', 'station_code', $station_code])
                ->orderBy(['time_point' => SORT_DESC])
                ->limit(1);
        if($time != NULL)
        {
            $query->AndWhere(['=', 'time_point', $time]);
        }
        $result = $query->all();
        if(count($result) > 0)
        {
            $result[0]['PM25']=$result[0]['pm2_5'];
            $result[0]['AQI']=$result[0]['aqi'];
            $result[0]['status'] = '2';
            unset($result[0]['pm2_5']);
            unset($result[0]['aqi']);
        }
        else
            $result[0]['status'] = 0;
        return $result;
    }

    public function queryStationCode($longitude, $latitude)
    {
        $query = (new\yii\db\Query())
                ->select('*')
                ->from('area_position')
                ->Where(['between', 'Longitude', $longitude-1, $longitude+1])
                ->AndWhere(['between', 'Latitude', $latitude-1, $latitude+1]);
        $models = $query->all();

        $min_diff = 999999;
        $station_code = NULL;
        foreach ($models as $model) {
            $area = abs(($model['longitude'] - $longitude)*($model['latitude'] - $latitude));
            if($area < $min_diff)
            {
                $min_diff = $area;
                $station_code = $model['station_code'];
            }
        }
        return $station_code;
    }
}