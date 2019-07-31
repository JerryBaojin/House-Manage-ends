<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['namespace'=>'Api'],function(){
    Route::get('/',function(){
        return 2;
    });
    Route::post('auth/register','AuthController@register');
    Route::post('login/account','AuthController@login');
    Route::get('captcha','AuthController@sendcode');
    Route::post('resetpwd','AuthController@resetpwd');
    Route::post('upload','CommonController@upload');
    Route::post('remove','CommonController@remove');
});

Route::group(['namespace'=>'api','middleware'=>'refresh.token'],function(){
    Route::post('auth/logout','AuthController@logout');
    Route::get('currentUser','AuthController@getUserInfo');
    Route::post('updateUser','AuthController@updateUser');
    //配置中心
    Route::post('config/addFeesConfigModel','Config\ConfigController@addFeesConfigModel');
    Route::get('config/getCurrentAllData','Config\ConfigController@getCurrentAllData');
    Route::post('config/updateConfig','Config\ConfigController@updateConfig');
    Route::post('config/deleteConfig','Config\ConfigController@deleteConfig');
    //房源配置
    Route::post('sourcemanage/addProject','SourceManage\SourceConfig@addProject');
    Route::post('sourcemanage/deleteProject','SourceManage\SourceConfig@deleteProject');
    Route::post('sourcemanage/addCommunity','SourceManage\SourceConfig@addCommunity');
    Route::post('sourcemanage/deleteCommunity','SourceManage\SourceConfig@deleteCommunity');
    Route::get('sourcemanage/getcommunity','SourceManage\SourceConfig@getcommunity');
    Route::get('sourcemanage/getproject','SourceManage\SourceConfig@getproject');
    Route::post('sourcemanage/submitSourceHouse','SourceManage\SourceConfig@submitSourceHouse');
    //房源管理中心
    Route::post('sourcemanage/searchData','SourceManage\SourceCenter@Index');
    Route::post('sourcemanage/updateroom','SourceManage\SourceCenter@updaterooms');
    Route::post('sourcemanage/deleteRent','SourceManage\SourceCenter@deleteRent');
    //租赁订单管理
    Route::post('rentmange/addRenter','RentManage\RentManage@add');
    Route::post('rentmange/addPreOrder','RentManage\RentManage@addRenter');
    Route::get('rentmange/getOrder','RentManage\RentManage@getOrder');
    Route::post('rentmange/updateOrder','RentManage\RentManage@updateOrder');
    //房源信息
    Route::get('house/getOne','HouseController\Index@getHousesById');
    Route::get('house/getAll','HouseController\Index@getAll');

    // 账单
    Route::get('account/all', 'AccountController\Index@allOrder');
    Route::get('account/orders', 'AccountController\Index@account_list');
    Route::post('account/addOrder', 'AccountController\Index@addOrder');
    Route::post('account/users/{id}', 'AccountController\Index@deleteById');
});
