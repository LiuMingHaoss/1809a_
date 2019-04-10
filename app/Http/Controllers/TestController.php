<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
class TestController extends Controller
{
    //
    public function valid(){
       echo $_GET['echostr'];
    }
    //接收微信服务器推送
    public function wxEvent(){

        $content=file_get_contents('php://input');
        $data=simplexml_load_string($content);
        $openid=$data->FromUserName;
        $userInfo=$this->getUserInfo($openid);
        $info=[
            'openid'=>$userInfo['openid'],
            'nickname'=>$userInfo['nickname'],
            'sex'=>$userInfo['sex'],
            'country'=>$userInfo['country'],
            'province'=>$userInfo['province'],
            'city'=>$userInfo['city'],
            'subscribe_time'=>$userInfo['subscribe_time'],
        ];
        DB::table('user')->insert($info);
        $time =date('Y-m-d H:i:s');
        $str =$time . $content . "\n";
        is_dir('logs')or mkdir('logs',0777,true);
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
    public function getUserInfo($openid){
        $access_token=$this->getAccesstoken();
        $userInfo=file_get_contents('https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN');
        $arr=json_decode($userInfo,true);
        return $arr;
    }
}
