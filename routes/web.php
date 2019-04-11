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
Route::any('/index/getMenu','TestController@getMenu');
