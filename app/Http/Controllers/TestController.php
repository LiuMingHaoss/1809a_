<?php

namespace App\Http\Controllers;

use App\Model\wx_user;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Model\Goods;
class TestController extends Controller
{
    //
    public function valid(){
       echo $_GET['echostr'];
    }
    //接收微信服务器推送
    public function wxEvent(){

        $content=file_get_contents('php://input');

        //记录日志
        $time =date('Y-m-d H:i:s');
        $str =$time . $content . "\n";
        is_dir('logs')or mkdir('logs',0777,true);
        file_put_contents("logs/wx_event.log",$str,FILE_APPEND);

        //解析XML 将xml字符串转化为对象
        $data=simplexml_load_string($content);

        $wx_id =$data->ToUserName;      //公众号id
        $event=$data->Event;            //事件类型
        $openid=$data->FromUserName;    //用户openid


        if($event=='subscribe'){        //用户关注事件
            $local_user=DB::table('user')->where(['openid'=>$openid])->first();
            if($local_user){
                DB::table('user')->where(['openid'=>$openid])->update(['status'=>1]);
                echo  '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName><FromUserName><![CDATA['.$wx_id.']]></FromUserName><CreateTime>'.time().'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['. '欢迎回来 '. $local_user->nickname .']]></Content></xml>';
            }else{
                //获取用户信息
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
                    'status'=>1
                ];

                //用户信息入库
                $res=DB::table('user')->insert($info);
                if($res){
                    echo '添加成功';
                }else{
                    echo '添加失败';
                }
                echo '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName><FromUserName><![CDATA['.$wx_id.']]></FromUserName><CreateTime>'.time().'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['. '欢迎关注 '. $userInfo['nickname'] .']]></Content></xml>';
            }
        }else if($event=='unsubscribe'){
            DB::table('user')->where(['openid'=>$openid])->update(['status'=>0]);
        }else if(isset($data->MsgType)){
            if($data->MsgType=='text'){     //用户发送文字信息

                $userInfo=$this->getUserInfo($openid);
                $Content=$data->Content;
                //自动回复天气
                if(strpos($Content,'+天气')){
                    //获取城市名称
                    $city=explode('+',$Content)[0];
                    $url='https://free-api.heweather.net/s6/weather/now?location='.$city.'&key=HE1904161034261454';
                    $arr=json_decode(file_get_contents($url),true);

                    if(isset($arr['HeWeather6']['0']['now'])){
                        $cond_txt=$arr['HeWeather6']['0']['now']['cond_txt'];
                        $fl=$arr['HeWeather6']['0']['now']['tmp'];
                        echo '<xml>
                                <ToUserName><![CDATA['.$openid.']]></ToUserName>
                                <FromUserName><![CDATA['.$wx_id.']]></FromUserName><CreateTime>'.time().'</CreateTime>
                                <MsgType><![CDATA[text]]></MsgType>
                                <Content><![CDATA['. '城市:'.$city ."\n".'天气:'.$cond_txt."\n".'温度:'.$fl.']]></Content>
                              </xml>';
                    }else{
                        echo '<xml>
                                <ToUserName><![CDATA['.$openid.']]></ToUserName>
                                <FromUserName><![CDATA['.$wx_id.']]></FromUserName><CreateTime>'.time().'</CreateTime>
                                <MsgType><![CDATA[text]]></MsgType>
                                <Content><![CDATA['. '该城市不支持'.']]></Content>
                              </xml>';
                    }


                }else if($Content=='最新商品'){
                    $goodsInfo=DB::table('shop_goods')->orderBy('create_time','desc')->limit(5)->get()->toArray();
                    foreach($goodsInfo as $k=>$v){
                        $img_url='http://1809liuminghao.comcto.com/goodsImg/'.$v->goods_img;
                        $desc_url='http://1809liuminghao.comcto.com/weixin/goods?goods_id='.$v->goods_id;
                        echo '
                        <xml>
                          <ToUserName><![CDATA['.$openid.']]></ToUserName>
                          <FromUserName><![CDATA['.$wx_id.']]></FromUserName>
                          <CreateTime>'.time().'</CreateTime>
                          <MsgType><![CDATA[news]]></MsgType>
                          <ArticleCount>1</ArticleCount>
                          <Articles>
                            <item>
                              <Title><![CDATA['.$v->goods_name.']]></Title>
                              <Description><![CDATA['.$v->goods_desc.']]></Description>
                              <PicUrl><![CDATA['.$img_url.']]></PicUrl>
                              <Url><![CDATA['.$desc_url.']]></Url>
                            </item>
                          </Articles>
                        </xml>';
                    }


                }

                $info=[
                  'openid'=>$userInfo['openid'],
                  'nickname'=>$userInfo['nickname'],
                  'content'=>$Content,
                    'create_time'=>$data->CreateTime,
                ];
                $res=DB::table('wx_text')->insert($info);
                if($res){
                    echo '信息入库成功';
                }else{
                    echo '信息入库失败';
                }
                echo '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName><FromUserName><![CDATA['.$wx_id.']]></FromUserName><CreateTime>'.time().'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['.$Content.']]></Content></xml>';

            }else if($data->MsgType=='image'){         //用户发送图片
                //请求地址
                $url='https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$this->getAccesstoken().'&media_id='.$data->MediaId;
                //接口数据
                $clinet=new Client();
                $response=$clinet->request('GET',$url);

                //获取文件名称
                $file_info=$response->getHeader('Content-disposition');
                $file_name = substr(md5(time().mt_rand(10000,99999)),10,8);
                $file_newname = $file_name.'_'.substr(rtrim($file_info[0],'"'),-20);

                //保存图片
                $image=$response->getBody();
                $wx_image_path = 'wx_media/images/'.$file_newname;
                $rr = Storage::disk('local')->put($wx_image_path,$image);
                var_dump($rr);

                //图片信息入库
                $userInfo=$this->getUserInfo($openid);
                $info=[
                    'openid'=>$userInfo['openid'],
                    'create_time'  => time(),
                    'msg_type'  => 'image',
                    'image_path'=>$wx_image_path
                ];
                $res=DB::table('wx_image')->insert($info);
                if($res){
                    echo '图片信息入库成功';
                }else{
                    echo '图片信息入库失败';
                }
                echo '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName><FromUserName><![CDATA['.$wx_id.']]></FromUserName><CreateTime>'.time().'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['.'图片不错 '.']]></Content></xml>';

            }else if($data->MsgType=='voice'){      //用户发送语音
                //请求地址
                $url='https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$this->getAccesstoken().'&media_id='.$data->MediaId;
                //接口数据
                $clinet=new Client();
                $response=$clinet->request('GET',$url);

                //获取文件名称
                $file_info=$response->getHeader('Content-disposition');
                $file_name = substr(md5(time().mt_rand(10000,99999)),10,8);
                $file_newname = $file_name.'_'.substr(rtrim($file_info[0],'"'),-20);

                //存入文件夹
                $voice=$response->getBody();
                $wx_voice_path = 'wx_media/vocie/'.$file_newname;
                $rr = Storage::disk('local')->put($wx_voice_path,$voice);
                var_dump($rr);

                //语音信息入库
                $userInfo=$this->getUserInfo($openid);
                $info=[
                    'openid'=>$userInfo['openid'],
                    'create_time'  => time(),
                    'msg_type'  => 'voice',
                    'voice_path'=>$wx_voice_path
                ];
                $res=DB::table('wx_voice')->insert($info);
                if($res){
                    echo '语音信息入库成功';
                }else{
                    echo '语音信息入库失败';
                }
                echo '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName><FromUserName><![CDATA['.$wx_id.']]></FromUserName><CreateTime>'.time().'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['.'说话大点声 '.']]></Content></xml>';

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
    //消息群发
    public function sendmsg($user_openid,$msg){
        $url='https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token='.$this->getAccesstoken();
        $send_arr=[
          'touser'=>$user_openid  ,
            'msgtype'=>'text',
            'text'=>[
                'content'=>$msg
            ]
        ];
        $msg_json=json_encode($send_arr,JSON_UNESCAPED_UNICODE);
        $clinet=new Client();
        $response=$clinet->request('POST',$url,[
            'body'=>$msg_json
        ]);
        return $response->getBody();
    }

    //群发
    public function send(){
        $user=wx_user::all()->toArray();
        $user_openid=array_column($user,'openid');
        $msg ='hellow  aa';
        $response=$this->sendmsg($user_openid,$msg);
        echo $response;
    }

    //商品详情
    public function goodsdesc(){

        //计算签名
        $nonceStr=Str::random(10);
        $ticket = getTicket();
        $timestamp=time();
        $current_url=$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
//        echo 'nonceStr:'.$nonceStr;echo '</br>';
//        echo 'ticket:'.$ticket;echo '</br>';
//        echo 'timestamp:'.$timestamp;echo '</br>';
//        echo 'current_url:'.$current_url;echo '</br>';

        $string = "jsapi_ticket=$ticket&noncestr=$nonceStr&timestamp=$timestamp&url=$current_url";
        $sign=sha1($string);

        $js_config=[
            'appId'=>env('WX_APPID'),   //公众号appid
            'timestamp'=>$timestamp,            //时间戳
            'nonceStr'=>$nonceStr,    //随机字符串
            'signature'=>$sign,                //签名

        ];
        return view('weixin.goods',['jsconfig'=>$js_config]);
    }
}
