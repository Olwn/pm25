<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
namespace app\modules\restful\controllers;
use yii;
use yii\console\Controller;
use yii\log\Logger;
$RootDir = $_SERVER['DOCUMENT_ROOT'];
use app\models\DeviceData as DeviceData;
use app\models\UrbanAir as UrbanAir;
$fireDir1 = "$RootDir/pm25/models/DeviceData.php";
$fireDir2 = "$RootDir/pm25/models/UrbanAir.php";
include($fireDir1);
include($fireDir2);

define('PI',3.1415926535898);
define('EARTH_RADIUS',6378.137);
date_default_timezone_set('PRC');

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Ming Yang <yougmark@icloud.com>
 * @since 2.0
 */
class CreateController extends Controller
{
    private $urlStations = "http://www.pm25.in/api/querys/all_cities.json";
    private $urlCities = "http://www.pm25.in/api/querys/aqi_ranking.json";
    private $urlDevices = "http://api.novaecs.com/?key=aidhe38173yfh&fun=getLastData&param=1000-A215";
    private $urlUrbanAir = "http://urbanair.msra.cn/U_Air/SearchGeoPoint?Culture=zh-CN&Standard=0";
    private $cities = array(
        "Beijing" => "http://urbanair.msra.cn/U_Air/GetAllCity?CityId=001&Standard=0&category=1&station=false&Lat_bottom=39.600532656689126&Lat_up=40.22306084378363&Lng_left=115.74797864062502&Lng_right=117.06633801562502",
        "Shanghai" => "http://urbanair.msra.cn/U_Air/GetAllCity?CityId=002&Standard=0&category=1&station=false&Lat_bottom=30.7&Lat_up=31.7&Lng_left=120.8&Lng_right=122.1",
        "Xiamen" => "http://urbanair.msra.cn/U_Air/GetAllCity?CityId=288&Standard=0&category=1&station=false&Lat_bottom=24.2&Lat_up=24.7&Lng_left=117.5&Lng_right=118.6",
        );
    private $cityIds = array(
        "Beijing" => 1,
        "Shanghai" => 2,
        "Xiamen" => 3,
        );
    private $datePath;
    private $token;
    private $dateLastQuery;

    public function init()
    {
        parent::init();
        $this->token = require(__DIR__.'/token.php');
        $this->datePath = __DIR__ . "/dateLastQuery.txt";
        $this->dateLastQuery = $this->getDateLastQuery();
        set_time_limit(0);//0表示没有限制
        ini_set('memory_limit','500M');
    }
    /**
     * This command echoes what you have entered as the message.
     * @param string $message the message to be echoed.
     */
    public function actionIndex($message = 'hello world')
    {
        //echo $message . "\n";
        $this->actionCreate();
    }

    /**
     * This command create new records of city air qualities.
     */
    public function actionData_pm25in()
    {
        $allStationsJsonData = $this->queryAllStationsJSONData();
        $mixed = json_decode($allStationsJsonData, true);
        if (isset($mixed['error']))
        {
            echo $mixed['error'];
        }
        else 
        {
            echo "Dealing with " . count($mixed) . " records.";
			if (count($mixed) != 0)
			{
				$class = 'app\models\AirQuality';
				foreach ($mixed as $value)
				{
					$this->saveModel($class, $value);
				}
			}
        }
        unset($allStationsJsonData);
        unset($mixed);
        $allCitiesJsonData = $this->queryOnlyCitiesJSONData();
        $mixed = json_decode($allCitiesJsonData, true);
        if (isset($mixed['error']))
        {
            echo $mixed['error'];
        }
        else
        {
            echo "Dealing with " . count($mixed) . " records.";
			if (count($mixed) != 0)
			{
				$class = 'app\models\CityAir';
				foreach ($mixed as $value)
				{
					$this->saveModel($class, $value);
				}
			}
        }
        unset($allCitiesJsonData);
        unset($mixed);
    }

    /**
     * This function get the data from mobile phones and save them in database.
     */
    public function actionData_mobile()
    {
        echo 'xxx';
        //echo $params;
    }

    /**
     * This function get the data from http://api.novaecs.com and save them in database.
     */
    public function actionData_device()
    {
        $deviceJsonData = $this->queryDeviceJSONData();
        //return $deviceJsonData;
        $mixed = json_decode($deviceJsonData, false)->devs;
        if (isset($mixed['error']))
        {
            echo $mixed['error'];
        }
        else 
        {
            echo "Dealing with " . count($mixed) . " records.\n";
            if (count($mixed) != 0)
            {
                $class = 'app\models\DeviceData';
                foreach ($mixed as $value)
                {
                    if(is_null($value->data))
                        continue;
                    $deviceData = new DeviceData();
                    $deviceData->devid = $value->devId;
                    foreach ($value->data as $oneData) 
                    {
                        switch ($oneData->tp) 
                        {
                               case 'wendu':
                                   $deviceData->temperature = $oneData->v;
                                   break;
                               case 'shidu':
                                    $deviceData->humidity = $oneData->v;
                                    break;
                               case  'tvoc':
                                    $deviceData->tvoc = $oneData->v;
                                    break;
                               case 'co2':
                                    $deviceData->co2 = $oneData->v;
                                    break;
                               case 'pm25':
                                    $deviceData->pm25 = $oneData->v;
                                    break;
                               default:
                                   break;
                           }   
                    }
                    echo $deviceData->temperature . $deviceData->pm25;
                    $deviceData->time_point = date('Y-m-d H:i:s',$value->data[0]->t);
                    try{
                        //Liechuan ou: If the parameter is 'true', the save proccedure would be failed. I seems the model rules is bad!?!
                        if(!$deviceData->save(false))
                            echo "failed";
                    }
                    catch(\Exception $e)
                    {
                        echo $e->getMessage();
                    }
                    
                }
            }
        }

    }

    private function getmicrotime()
    {
        list($usec, $sec) = explode(" ",microtime());
        return ((float)$usec + (float)$sec);
    }

    public function actionUrban_air()
    {
        $class = 'app\models\UrbanAir';
        $urls = array();
        $c = 0;

        foreach ($this->cities as $city => $allpoints_url) {
            $points = file_get_contents($allpoints_url);
            $points = json_decode($points);
            var_dump(count($points->AllCity));
            //continue;
            foreach ($points->AllCity as $point) {
                $Lon = ($point->Lng_max + $point->Lng_min)/2;
                $Lat = ($point->Lat_max + $point->Lat_min)/2;
                $path = $this->urlUrbanAir . "&longitude=" . $Lon . "&latitude=" . $Lat . "&cityId=" . $this->cityIds[$city];
                $urls[$c] = $path;
                $c = $c + 1;
                if($c == 10)
                {
                    $results = $this->rolling_curl($urls, 100);
                    foreach ($results as $key => $value) {
                        $this->saveUrbanAir($key, $value);
                    }
                    sleep(0.2);
                    $c = 0;
                    $urls = array();
                }
            }
        }
        echo $this->error_count;
        
    }

    private function saveUrbanAir($url, $data)
    {
        $mixed = $data["results"];
        if(is_null($mixed))
            return;
        $query =  parse_url($url, PHP_URL_QUERY);
        parse_str($query);
        
        $urbanData = new UrbanAir();
        $urbanData->aqi = $mixed->AQI;
        $urbanData->no2 = $mixed->NO2;
        $urbanData->pm25 = $mixed->PM25;
        $urbanData->pm10 = $mixed->PM10;
        $urbanData->wind = $mixed->Wind;
        $urbanData->pressure = $mixed->Pressure;
        $urbanData->temperature = $mixed->Temperature;
        $urbanData->humidity = $mixed->Humidity;
        $urbanData->longitude = $longitude;
        $urbanData->latitude = $latitude;
        $urbanData->city = $cityId;
        $objDateTIme = \DateTime::createFromFormat("Y/n/j h:i A", "2015/" . $mixed->UpdateTime);
        $urbanData->time_point = $objDateTIme->format('Y-m-d H:i:s'); 
        
        $urbanData->save(false);
    }

    private function saveModel($class, $value)
    {
        $model = new $class();
        $class = substr($class, strripos($class, "\\")+1);
        $data = [$class => $value];
        if (!$model->load($data))
        {
            \Yii::getLogger()->log("FAIL LOADING DATA: " , Logger::LEVEL_INFO);
        }
        try
        {
            if (!$model->save())
            {
                \Yii::getLogger()->log("FAIL SAVING DATA: " , Logger::LEVEL_INFO);
                echo "FAIL SAVING DATA: \n" . implode("\n", $value);
                var_dump($model->getErrors());
            }
        }
        catch (\Exception $e) 
        {
            echo 'EXCEPTION: '. $e->getMessage(), "\n";
            \Yii::getLogger()->log("FAIL SAVING DATA: " . $e->getMessage(), Logger::LEVEL_INFO);
        }
    }

    /**
     * Query json data from pm25.in
     */
    private function queryAllStationsJSONData()
    {
        echo "Start querying air quality data from http://pm25.in\n";
        //$path = __DIR__ . "/../all_cities.json";
        $path = $this->urlStations . "?token=" . $this->token;
        try 
        {
            $content = file_get_contents($path);
        }
        catch (\Exception $e)
        {
            echo 'EXCEPTION: ' . $e->getMessage(), "\n";
            return null;
        }
        //print_r($content);
        return $content;
    }


    private function queryOnlyCitiesJSONData()
    {
        echo "Start querying air quality data from http://pm25.in\n";
        //$path = __DIR__ . "/../all_cities.json";
        $path = $this->urlCities . "?token=" . $this->token;
        try 
        {
            $content = file_get_contents($path);
        }
        catch (\Exception $e)
        {
            echo 'EXCEPTION: ' . $e->getMessage(), "\n";
            return null;
        }
        //print_r($content);
        return $content;
    }

    private function queryDeviceJSONData()
    {
        echo "Start querying air data from novaecs.com\n";
        //$path = __DIR__ . "/../all_cities.json";
        $path = $this->urlDevices;
        try 
        {
            $content = file_get_contents($path);
        }
        catch (\Exception $e)
        {
            echo 'EXCEPTION: ' . $e->getMessage(), "\n";
            return null;
        }
        //print_r($content);
        return $content;
    }

    /**
     * Restore last query date
     */
    private function getDateLastQuery()
    {
        $file = fopen($this->datePath, "a+");
        if (!filesize($this->datePath))
        {
            $now = new \DateTime();
            $now->sub(new \DateInterval('PT1H'));
            return $now;
        }
        $content = fread($file, filesize($this->datePath));
        fclose($file);
        return new \DateTime($content);
    }

    /**
     * Save last query date
     */
    private function saveDateLastQuery()
    {
        $file = fopen($this->datePath, "w"); 
        fwrite($file, $this->dateLastQuery->format('Y-m-d H:i:s'));
        fclose($file);
    }

    private function GetDistance($lat1, $lng1, $lat2, $lng2)
    { 
        $radLat1 = $lat1 * (PI / 180);
        $radLat2 = $lat2 * (PI / 180);
       
        $a = $radLat1 - $radLat2; 
        $b = ($lng1 * (PI / 180)) - ($lng2 * (PI / 180)); 
       
        $s = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1)*cos($radLat2)*pow(sin($b/2),2))); 
        $s = $s * EARTH_RADIUS; 
        $s = round($s * 10000) / 10000; 
        return $s; 
    }

    private function rolling_curl($urls, $delay) 
    {
        $queue = \curl_multi_init();
        $map = array();
     
        foreach ($urls as $url) {
            $ch = curl_init();
     
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 50);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_NOSIGNAL, true);
     
            curl_multi_add_handle($queue, $ch);
            $map[(string) $ch] = $url;
        }
     
        $responses = array();
        do {
            while (($code = curl_multi_exec($queue, $active)) == CURLM_CALL_MULTI_PERFORM) ;
     
            if ($code != CURLM_OK) { break; }
     
            // a request was just completed -- find out which one
            while ($done = curl_multi_info_read($queue)) {
     
                // get the info and content returned on the request
                $info = curl_getinfo($done['handle']);
                $error = curl_error($done['handle']);
                $results = $this->callback(curl_multi_getcontent($done['handle']), $delay);
                $responses[$map[(string) $done['handle']]] = compact('info', 'error', 'results');
     
                // remove the curl handle that just completed
                curl_multi_remove_handle($queue, $done['handle']);
                curl_close($done['handle']);
            }
     
            // Block for data in / output; error handling is done by curl_multi_exec
            if ($active > 0) {
                curl_multi_select($queue, 0.5);
            }
     
        } while ($active);
     
        curl_multi_close($queue);
        return $responses;
    }
    private $error_count = 0;
    private function callback($data, $delay) 
    {
        $mixed = json_decode($data, false);
        if(is_null($mixed))
            return;
        else if(is_null($mixed->PM25))
        {
            $this->error_count = $this->error_count + 1;
            return;
        }
        return $mixed;
               
        //echo $end - $begin;

    }

}
