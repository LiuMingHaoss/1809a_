<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
class TestController extends Controller
{
    //
    public function valid(){
       echo $_GET['echostr'];
    }
    //接收微信服务器推送
    public function wxEvent(){

        $content=file_get_contents('php://input');
        $time =date('Y-m-d H:i:s');
        $str =$time . $content . "\n";
        file_put_contents("logs/wx_event.log",$str,FILE_APPEND);
        echo "SUCCESS";
    }
    //获取微信 AccessToken
    public function getAccesstoken(){
        $key='wx_accesstoken';
        $access_token=Redis::get($key);
        if(!$access_token){
            $url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.env('WX_APPID').'&secret='.env('WX_SECRET');
            $response=json_decode(file_get_contents($url),true);
            //缓存accesstoken
            Redis::set($key,$response['access_token']);
            Redis::expire($key,3600);
            $access_token=$response['access_token'];
        }
        return $access_token;
    }

    //获取用户信息
    public function getUserInfo(){
        $access_token=$this->getAccesstoken();
        $userInfo=file_get_contents('https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid=og1Jd1KlcDOxObfJuCnzCe5-CZ68&lang=zh_CN');
        $arr=json_decode($userInfo,true);
        print_r($arr);
    }
}
