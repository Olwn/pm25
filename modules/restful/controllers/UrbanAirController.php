<?php

namespace app\modules\restful\controllers;

use Yii;
use yii\data\ActiveDataProvider;
date_default_timezone_set('PRC');

class UrbanAirController extends CheckTokenController
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
        $sql = "select avg(aqi) as AQI, avg(pm2_5) as PM25, date_format(time_point, '%Y-%m-%d') as Date
                 from air_quality where station_code ='" . $station_code . "'group by date_format(time_point, '%Y-%m-%d') 
                 order by time_point desc limit " . $conditions['days'];
        $command = $connection->createCommand($sql);    
        $result['data'] = $command->queryAll();   
        return $result;
    }
    public function actionSearchHistory()
    {
        $ret = array(
            'status' => 1,
            'message' => 'successfully get data',
            'count' => 0,
            'data' => ''
            );
        $conditions = Yii::$app->request->get();
        $date = $conditions['time_point'];
        //if(!isset($conditions['use_station']) || $conditions['use_station'] != '1')
        {
            $j = 0;
            $result = array();
            $station_code = $this->queryStationCode($conditions['longitude'], $conditions['latitude']);
			$ret['station_code']=$station_code;
            for ($i=0; $i < 24; $i++) 
            { 
                $time_point = $date. ' ';
                if($i < 10)
                    $time_point = $time_point . '0' . strval($i);
                else
                    $time_point = $time_point . strval($i);
                $time_point = $time_point . ':00:00';
                $conditions['time_point'] = $time_point;
                $data = $this->queryDataBaseUrbanAir($conditions['longitude'], $conditions['latitude'], $conditions['time_point']);
                if($data == NULL)
                    $data = $this->queryDatabasePM25ByCode($station_code, $conditions['time_point']);
                if($data != NULL)
                $result[$j++] = $data;
            }
        }
        
        if(count($result) == 0)
        {
            $ret['status'] = 0;
            $ret['message'] = 'no data of this position';
        }
        $ret['data'] = $result;
        $ret['count'] = count($result);
        return $ret;

    }
    public function actionSearch()
    {
        $conditions = Yii::$app->request->get();
        $ret = array(
            'status' => 1,
            'message' => 'successfully get data',
            'data' => NULL
            );
        
        if(isset($conditions['time_point']))
            $ret['data'] = $this->queryDataBaseHistory($conditions);
        else if(isset($conditions['use_station']) && $conditions['use_station']=='1')
            $ret['data'] = $this->queryDatabasePM25($conditions['longitude'], $conditions['latitude'], NULL);
        else
        {
            //Search Urban Air first
            $url = "http://urbanair.msra.cn/U_Air/SearchGeoPoint?Culture=zh-CN&Standard=0";
            $ret['data'] = $this->queryUrbanAir($conditions['longitude'], $conditions['latitude']);    
            //If it's none in urban air, search station data
            if(!isset($ret['data']))
                $ret['data'] = $this->queryDatabasePM25($conditions['longitude'], $conditions['latitude'], NULL);
        }
        if($ret['data'] == NULL)
        {
            $ret['status'] = 0;
            $ret['message'] = 'no data about this position';
        }
        return $ret;

    }

    public function queryUrbanAir($longitude, $latitude)
    {
        $url = "http://urbanair.msra.cn/U_Air/SearchGeoPoint?Culture=zh-CN&Standard=0";
        try
        {
        $result = file_get_contents($url . "&Longitude=" . $longitude . "&Latitude=" . $latitude, false, 
                    stream_context_create(array('http' => array('method'=>"GET", 'timeout'=>60))));
        }
        catch(yii\base\ErrorException $e)
        {
            return NULL;
        }

        $obj = json_decode($result);
        if(!isset($obj->PM25))
            return NULL;
		$am_pm = '';
		if(strpos($obj->UpdateTime, 'PM') != -1)
				$am_pm = 'A';
		else
				$am_pm = 'a';
        $t = \DateTime::createFromFormat("Y-m-d h:i " . $am_pm, date('Y-m-d ') . $obj->UpdateTime)->format('Y-m-d H:i:s');
        return array('PM25'=> $obj->PM25, 'AQI' => $obj->AQI, 'time_point' => $t, 'source' => 1);
    }

    public function queryDataBaseHistory()
    {
        $conditions = Yii::$app->request->get();
		if (!isset($conditions['latitude'], $conditions['longitude'], $conditions['time_point']))
		{
			return null;
		}

        if(isset($conditions['use_station']) && $conditions['use_station']=='1')
            return $this->queryDatabasePM25($conditions['longitude'], $conditions['latitude'], $conditions['time_point']);
        else
        {
            $result = $this->queryDataBaseUrbanAir($conditions['longitude'], $conditions['latitude'], $conditions['time_point']);
            if($result != NULL)
                return $result;
            else
                return $this->queryDatabasePM25($conditions['longitude'], $conditions['latitude'], $conditions['time_point']);
        }
        
    }

    public function queryDataBaseUrbanAir($longitude, $latitude, $time_point)
    {
        $query = (new \yii\db\Query())
            ->select('*')
            ->from('urban_air')
            ->Where(['=','time_point', $time_point]);
        
        $lat = (double)$latitude;
        $lon = (double)$longitude;
        $query->AndWhere(['between', 'latitude', $lat-0.05, $lat+0.05]);
        $query->AndWhere(['between', 'longitude', $lon-0.05, $lon+0.05]);

        $model = new $this->modelClass;
        $models = $query->all();
        if(count($models) == 0)
            return NULL;

        $min_diff = 99999;
        $index = 0;
        for($x=0; $x<count($models); $x++) {
            # code...
            $model = $models[$x];
			$distance = self::distance($model['latitude'], $model['longitude'], $lat, $lon);
            if($distance < $min_diff)
            {
                $index = $x;
                $min_diff = $distance;
            }
        }

        return array('PM25' => $models[$index]['pm25'], 'AQI' => $models[$index]['aqi'], 'time_point' => $models[$index]['time_point'], 'source' => 1);
    }
    
    public function queryDatabasePM25ByCode($station_code, $time)
    {
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
            return array('PM25' => $result[0]['pm2_5'], 'AQI' => $result[0]['aqi'], 'time_point' => $result[0]['time_point'], 'source' => 2);
        else
            return NULL;
    }
    public function queryDatabasePM25($longitude, $latitude, $time)
    {
        $station_code = $this->queryStationCode($longitude, $latitude);
		if (is_null($station_code)) return NULL;
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
            return array('PM25' => $result[0]['pm2_5'], 'AQI' => $result[0]['aqi'], 'time_point' => $result[0]['time_point'], 'source' => 2);
        else
            return NULL;
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
			$lat1 = $model['latitude'];
			$lon1 = $model['longitude'];
			$lat2 = $latitude;
			$lon2 = $longitude;
			$dist = self::distance($lat1, $lon1, $lat2, $lon2);
			 
            if($dist < $min_diff)
            {
                $min_diff = $dist;
                $station_code = $model['station_code'];
            }
        }
        return $station_code;
    }
    public static function distance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
    {
      // convert from degrees to radians
      $latFrom = deg2rad($latitudeFrom);
      $lonFrom = deg2rad($longitudeFrom);
      $latTo = deg2rad($latitudeTo);
      $lonTo = deg2rad($longitudeTo);

      $lonDelta = $lonTo - $lonFrom;
      $a = pow(cos($latTo) * sin($lonDelta), 2) +
        pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
      $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

      $angle = atan2(sqrt($a), $b);
      return $angle * $earthRadius;
    }
}
