<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Qcloud\Sms\SmsSingleSender;
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;



    protected function send_sms( $token , $phone )
    {
      if (preg_match("/^1[3456789]\d{9}$/",$phone)) {
        $mathRand = mt_rand("1000","9999");
        try {
            $ssender = new SmsSingleSender(env("Qcloud_AppId"), env("Qcloud_AppKey"));
            $params = [$mathRand,"1"];
             $result = $ssender->sendWithParam("86", $phone, 311521,$params, "");  // 签名参数未提供或者为空时，会使用默认签名发送短信
            if (json_decode($result)->errmsg== 'OK') {
                Redis::setex($token,60, 1234);
            }
            return $result;
        //    return '{"result":0,"errmsg":"OK","ext":"","sid":"8:N0zM4ofpFKozpVbFsKX20190416","fee":1}';
        } catch(\Exception $e) {
            Log::error($e);
            return  $this->error($e->getMessage());
        }
      }else{
        return  $this->error("手机格式错误");
      }
    }


    protected function getuserId(){
      $currenUserInfo =  auth('api')->user();
      return $currenUserInfo['id'];
    }

    protected function log($msg, $level = 'info')
    {
        Log::$level($msg);
    }

    protected function getHeader(Request $request, $name , $default = null)
    {
        return $request->header($name, $default);
    }

     /**
     * 操作错误跳转的快捷方法
     * @access protected
     * @param string $message 错误信息
     * @param mixed $options Ajax其它参数
     * @return void
     */
    protected function error($message = '', $options = [], $code = 0)
    {
        return $this->_ajaxReturn($message, 422, $options, $code);
    }
    /**
     * 操作成功跳转的快捷方法
     * @access protected
     * @param string $message 提示信息
     * @param string $jumpUrl 页面跳转地址false:不跳转,true:当前页面刷新
     * @param mixed $options Ajax其它参数
     * @return void
     */
    protected function success($message = '', $options = [], $code = 1)
    {
        return $this->_ajaxReturn($message, 200, $options, $code);
    }
    /**
     * ajax提交输出提示
     * @param string $message 提示信息
     * @param Boolean $status 状态
     * @param string $jumpUrl 页面跳转地址
     * @param mixed $options Ajax其它参数
     * @access private
     * @return void
     */
    protected function _ajaxReturn($message, $status = 200, $options = [], $code = 0)
    {
        $data['info'] = $message;
        $data['code'] = $code;
        $data['data'] = $options;
        $request = app()->make('request');
        return response($data, $status);
    }

}
