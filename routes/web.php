<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/info',function(){
    phpinfo();
});
Route::get('/', function () {
    return view('welcome');
});
Route::get('/index','TestController@valid');
Route::post('/index','TestController@wxEvent');
//获取access_token
Route::get('/index/getaccess_token','TestController@getAccesstoken');
//获取用户信息
Route::get('/index/getUserInfo','TestController@getUserInfo');
//自定义菜单
Route::get('/index/getMenu','TestController@getMenu');
//群发消息
Route::get('/index/send','TestController@send');

//微信支付
Route::get('/weixin/pay/test','Weixin\WxPayController@test');
Route::post('/weixin/pay/notify','Weixin\WxPayController@notify');       //微信支付回调地址

