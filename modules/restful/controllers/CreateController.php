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
    private $urlDevices = "http://api.novaecs.com/?key=aidhe38173yfh&fun=getLastData&param=1000-A215,1000-A043,1000-A2E7,1000-A2E6,1000-A3F3,1000-A3F1,1000-A3FC,1000-A3F0,1000-A3EE,1000-A3F5,1000-A3EF,1000-A3FA";
	//private $devices = array('1000-A215', '1000-A043'); 
    private $urlUrbanAir = "http://urbanair.msra.cn/U_Air/SearchGeoPoint?Culture=zh-CN&Standard=0";
    private $cities = array(
        "Beijing" => array(39.6, 40.2, 115.7,117.0),
        "Shanghai" => array(30.7,31.7, 120.8,122.1),
        "Xiamen" => array(24.2, 24.7, 117.5, 118.6),
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
            if (count($mixed) != 0)
	    {
		$class = 'app\models\AirQuality';
		foreach ($mixed as $value)
		{
	            DefaultController::saveModel($class, $value);
		}
	    }
        }
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
	$deviceJsonData = substr($deviceJsonData, 3);
        $mixed = json_decode($deviceJsonData, false)->devs;
        if (false)
        {
            echo "error from novaecs.com";
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

		$t = getdate($value->data[0]->t);
		$t_cov = mktime($t['hours'],round($t['minutes']/15)*15,0,$t['mon'],$t['mday'],$t['year']);
		//$t_cov = mktime($t['hours'],$t['minutes'],0,$t['mon'],$t['mday'],$t['year']);
		$deviceData->time_point = date('Y-m-d H:i:s',$t_cov);
                try{
                    if(!$deviceData->save(false))
                        echo "failed";
                }
                catch(\Exception $e){
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

        foreach ($this->cities as $city => $region) {
            $step = 0.01;
       	    $lats = range($region[0], $region[1], $step);
            $lngs = range($region[2], $region[3], $step);     
            for ($i = 0; $i < count($lats); $i++)
            for ($j = 0; $j < count($lngs); $j++)
            {
                $path = $this->urlUrbanAir . "&longitude=" . $lngs[$j] . "&latitude=" . $lats[$i] . "&cityId=" . $this->cityIds[$city];
                $urls[$c] = $path;
                $c = $c + 1;
                if($c == 50)
                {
                    $results = $this->rolling_curl($urls, 100);
                    foreach ($results as $key => $value) {
                        $this->saveUrbanAir($key, $value);
                    }
                    sleep(0.1);
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

        $t = date('Y-m-d H');
        $urbanData->time_point = $t; 
        
        $urbanData->save(false);
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
