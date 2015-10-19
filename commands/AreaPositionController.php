<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use yii\console\Controller;
use app\models\AreaPosition;
use yii\log\Logger;

/**
 *
 * @author Ming Yang <yougmark@icloud.com>
 * @since 2.0
 */
class AreaPositionController extends Controller
{
    //API控制台申请得到的ak（此处ak值仅供验证参考使用）
    private $ak;
    //应用类型为for server, 请求校验方式为sn校验方式时，系统会自动生成sk，可以在应用配置-设置中选择Security Key显示进行查看（此处sk值仅供验证参考使用）
    private $sk;

    public function init()
    {
        parent::init();
        $this->ak = require(__DIR__.'/ak.php');
        $this->sk = require(__DIR__.'/sk.php');
    }

    /**
     */
    public function actionIndex($message = 'hello world')
    {
        //echo $message . "\n";
        $this->actionUpdateCoordinate();
    }

    /**
     * This command create new records of city air qualities.
     */
    public function actionUpdateCoordinate($obscure = false)
    {
        $rows = (new \yii\db\Query())
            ->select(['area', 'position_name'])
            ->distinct()
            ->from('air_quality')
            ->all();

        //以Geocoding服务为例，地理编码的请求url，参数待填
        $url = "http://api.map.baidu.com/geocoder/v2/?address=%s&city=%s&output=%s&ak=%s&sn=%s";
        $urlObscure = "http://api.map.baidu.com/geocoder/v2/?address=%s&output=%s&ak=%s&sn=%s";
         
        //get请求uri前缀
        $uri = '/geocoder/v2/';
         
        //地理编码的请求output参数
        $output = 'json';
         
        foreach($rows as $row)
        {
            if ($obscure)
            {
                $row['latitude'] = 0;
            }
            $model = AreaPosition::findOne($row);
            if ($model == null || $obscure)
            {
                if ($obscure)
                {
                    if ($model == null)
                        continue;
                }
                else 
                {
                    $model = new AreaPosition();
                    $model->load(['AreaPosition' => $row]);
                }
                //地理编码的请求中address参数
                $address = $model->position_name;
                $city = $model->area;
                //构造请求串数组
                echo "\nAddress: ". $address . "|City: " . $city . "\n";
                if ($obscure)
                {
                    $querystring_arrays = array (
                        'address' => $city.$address,
                        'output' => $output,
                        'ak' => $this->ak
                    );
                }
                else 
                {
                    $querystring_arrays = array (
                        'address' => $address,
                        'city'=> $city,
                        'output' => $output,
                        'ak' => $this->ak
                    );
                }
                 
                //调用sn计算函数，默认get请求
                $sn = $this->caculateAKSN($this->ak, $this->sk, $uri, $querystring_arrays);
                //请求参数中有中文、特殊字符等需要进行urlencode，确保请求串与sn对应
                if ($obscure)
                    $target = sprintf($urlObscure, urlencode($city.$address), $output, $this->ak, $sn);
                else
                    $target = sprintf($url, urlencode($address), urlencode($city), $output, $this->ak, $sn);
                $response = $this->queryJSONData($target);
                if ($response == null)
                    continue;
                var_dump($response);
                if (array_key_exists("status", $response) && 1 == $response['status'])
                {
                    echo "!! NO data for the position\n";
                    $model->latitude = 0;
                    $model->longtitude = 0;
                    $this->saveModel($model);
                    continue;
                }
                $location = $response['location'];
                $model->latitude = $location['lat'];
                $model->longtitude = $location['lng'];
                $this->saveModel($model);
            }
            else
            {
                echo "\nRecord already existed";
            }
        }
    }

    private function saveModel($model)
    {
        try
        {
            if (!$model->save())
            {
                echo "!!!FAIL SAVING DATA\n";
            }
        }
        catch(\Exception $e)
        {
            echo "!!EXCEPTION: " . $e->getMessage() . "\n";
        }
        
    }

    private function queryJSONData($target)
    {
        echo "Start querying coordinate data from Baidu\n";
        echo $target . "\n";
        try
        {
            $content = file_get_contents($target);
        }
        catch(\Exception $e)
        {
            echo 'EXCEPTION: ' . $e->getMessage() . "\n";
            return null; 
        }

        $mixed = json_decode($content, true);
        $status = $mixed['status'];
        if ( 0 != $status)
        {
            echo "!!!Wrong status: \n";
            var_dump($mixed);
            if (1 == $status)
            {
                return $mixed;
            }
            return null;
        }
        return $mixed['result'];
    }

    private function caculateAKSN($ak, $sk, $url, $querystring_arrays, $method = 'GET')
    {  
        if ($method === 'POST'){  
            ksort($querystring_arrays);  
        }  
        $querystring = http_build_query($querystring_arrays);  
        return md5(urlencode($url.'?'.$querystring.$sk));  
    }

    public function actionCoordinate($address, $city)
    {
        //以Geocoding服务为例，地理编码的请求url，参数待填
        $url = "http://api.map.baidu.com/geocoder/v2/?address=%s&output=%s&ak=%s&sn=%s";
         
        //get请求uri前缀
        $uri = '/geocoder/v2/';
         
        //地理编码的请求output参数
        $output = 'json';
         
        $address = $city.$address;
        //构造请求串数组
        $querystring_arrays = array (
            'address' => $address,
            'output' => $output,
            'ak' => $this->ak
        );
         
        //调用sn计算函数，默认get请求
        $sn = $this->caculateAKSN($this->ak, $this->sk, $uri, $querystring_arrays);
         
        //请求参数中有中文、特殊字符等需要进行urlencode，确保请求串与sn对应
        $target = sprintf($url, urlencode($address), $output, $this->ak, $sn);

        var_dump($this->queryJSONData($target));

    }

}
