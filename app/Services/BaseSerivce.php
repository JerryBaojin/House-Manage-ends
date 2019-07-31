<?php
namespace App\Services;
use App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
/**
 * Introduction 服务组件的基类
 */
class BaseSerivce
{

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
