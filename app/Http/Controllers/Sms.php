<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Redis;

class Sms{
  const EXPIRE_SEC = 1800;    // 过期时间间隔
  const RESEND_SEC = 60;     // 重发时间间隔
  const ONE_DAY_FREQ = 5;    // 每日向同一个手机号发短信的次数
  const ONE_DAY_IMEI_COUNT = 3; // 每日向同一个手机号发送短信的IMEI个数
  
  public $error = array();
  
  
  /**
   * 向指定手机号发送验证码
   * @param $mobile
   * @param $imei
   * @return bool
   */
  public function sendVerifyCode($mobile, $imei) {
    if(!$this->isMobile($mobile)) {
      $this->error = array('code' => -1, 'msg' => '这个手机号很奇葩哦，请正确输入后重试');
      return false;
    }
  
    $vcKey = 'VC_'.$mobile;
    $limitKey = 'VC_LIMIT_'.$mobile;
  
    // 验证码重发限制
    $data = json_decode(Redis::get($vcKey), true);
    if($data && time() < $data['resend_expire']) {
      $this->error = array('code' => -1, 'msg' => '短信已在1分钟内发出，请耐心等待');
      return false;
    }
  
    // 手机号及IMEI限制
    $sendCnt = Redis::zScore($limitKey, $imei);
    if($sendCnt && $sendCnt >= self::ONE_DAY_FREQ) {
      $this->error = array('code' => -1, 'msg' => '没收到短信?请稍等或检查短信是否被屏蔽');
      return false;
    }
    $imeiCnt = Redis::zCard($limitKey);
    if($imeiCnt >= self::ONE_DAY_IMEI_COUNT && !$sendCnt) {
      $this->error = array('code' => -1, 'msg' => '已超过验证码发送设备限制');
      return false;
    }
  
    // 获取验证码
    if(!$data) {
      $vc = strval(rand(100000, 999999));
      $data = array('vc' => $vc, 'resend_expire' => 0);
      Redis::set($vcKey, json_encode($data));
      Redis::expire($vcKey, self::EXPIRE_SEC); // 设置验证码过期时间
    }
    $vc = $data['vc'];
  
    $content = '安全验证码：'.$vc;
    $result = $this->send($mobile, $content);
    if($result) {
      // 重设重发时限
      $data['resend_expire'] = time() + self::RESEND_SEC;
      $ttl = Redis::ttl($vcKey);
      Redis::set($vcKey, json_encode($data));
      Redis::expire($vcKey, $ttl);
  
      // 设置手机号与IMEI限制
      Redis::zIncrBy($limitKey, 1, $imei);
      Redis::expireAt($limitKey, strtotime(date('Y-m-d',strtotime('+1 day'))));
    }
    return $result;
  }
  
  /**
   * 向指定手机号发送短信
   * @param $mobile
   * @param $content
   * @return bool
   */
  public function send($mobile, $content){
    // TODO 调用具体服务商API
    return true;
  }
  
  /**
   * 判断是否为合法手机号
   * @param $mobile
   * @return bool
   */
  private function isMobile($mobile) {
    if(preg_match('/^1\d{10}$/', $mobile))
      return true;
    return false;
  }
  
  /**
   * 验证短信验证码
   * @param $mobile
   * @param $vc
   * @return bool
   */
  public function checkVerifyCode($mobile, $vc) {
    $vcKey = 'VC_'.$mobile;
    $vcData = json_decode(Api_Common::redis()->get($vcKey), true);
    if($vcData && $vcData['vc'] === $vc) {
      return true;
    }
    return false;
  }
  
  /**
   * 清除验证码
   * @param $mobile
   */
  public function cleanVerifyCode($mobile) {
    $redis = Api_Common::redis();
    $vcKey = 'VC_'.$mobile;
    $limitKey = 'VC_LIMIT_'.$mobile;
    Redis::del($vcKey);
    Redis::del($limitKey);
  }
}