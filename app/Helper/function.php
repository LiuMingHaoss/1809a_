<?php
use Illuminate\Support\Facades\Redis;
    function test(){
        echo 'helper';
    }

    function getWxAccessToken(){
        $key='wx_access_token';
        $access_token=Redis::get($key);
        if($access_token){
            return $access_token;
        }else{
            $url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.env('WX_APPID').'&secret='.env('WX_SECRET');
            $arr=json_decode(file_get_contents($url),true);

            if(isset($arr['access_token'])){
                Redis::set($key,$arr['access_token']);
                Redis::expire($key,3600);
                return $arr['access_token'];
            }else{
                return false;
            }
        }
    }

    function getTicket(){
        $key='wx_ticket';
        $ticket=Redis::get($key);
        if($ticket){
            return $ticket;
        }else{
            //获取access_token
            $access_token=getWxAccessToken();
            //请求 ticket
            $url='https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$access_token.'&type=jsapi';
            $ticket=json_decode(file_get_contents($url),true);

            if(isset($ticket['ticket'])){
                Redis::set($key,$ticket['ticket']);
                Redis::expire($key,3600);
                return $ticket['ticket'];
            }else{
                return false;
            }
        }
    }