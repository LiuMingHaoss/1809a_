<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
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
        is_dir('logs')or mkdir('logs',0777,true);
        file_put_contents("logs/wx_event.log",$str,FILE_APPEND);
        $data=simplexml_load_string($content);
        $wx_id =$data->ToUserName;  //公众号id
        $event=$data->Event; //事件类型
        $openid=$data->FromUserName;
        if($event=='subscribe'){
            $local_user=DB::table('user')->where(['openid'=>$openid])->first();
            if($local_user){
                echo  '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName><FromUserName><![CDATA['.$wx_id.']]></FromUserName><CreateTime>'.time().'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['. '欢迎回来 '. $local_user->nickname .']]></Content></xml>';
            }else{
                $userInfo=$this->getUserInfo($openid);
                $info=[
                    'openid'=>$userInfo['openid'],
                    'nickname'=>$userInfo['nickname'],
                    'sex'=>$userInfo['sex'],
                    'country'=>$userInfo['country'],
                    'province'=>$userInfo['province'],
                    'city'=>$userInfo['city'],
                    'subscribe_time'=>$userInfo['subscribe_time'],
                    'headimgurl'=>$userInfo['headimgurl'],
                ];
                $res=DB::table('user')->insert($info);
                if($res){
                    echo '添加成功';
                }else{
                    echo '添加失败';
                }
                echo '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName><FromUserName><![CDATA['.$wx_id.']]></FromUserName><CreateTime>'.time().'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['. '欢迎关注 '. $userInfo['nickname'] .']]></Content></xml>';
            }
        }


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
    //自定义菜单
    public function getMenu(Request $request){
        //url
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$this->getAccesstoken();
        //接口数据
        $post_arr=[
          'button'=>[
              [
                  'type'=>'click',
                  'name'=>'点击有惊喜',
                  'key'=>'key_menu_001'
              ],
              [
              'type'=>'click',
              'name'=>'目录',
              'key'=>'key_menu_002'
              ],
          ]
        ];
        $json_str=json_encode($post_arr,JSON_UNESCAPED_UNICODE);
        //发送请求
        $clinet=new Client();
        $response=$clinet->request('POST',$url,[
            'body'=>$json_str
        ]);
        //处理响应
        $res_str=$response->getBody();
        $arr=json_decode($res_str,true);
        echo '<pre>';print_r($arr);

    }
}
