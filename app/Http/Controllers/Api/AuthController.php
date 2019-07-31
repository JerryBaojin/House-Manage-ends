<?php

namespace App\Http\Controllers\Api;

use JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Auth;
use App\Transformers\UserTransformer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use App\Models\User;
class AuthController extends Controller
{
    public function login(Request $request){
        $currentLoginWay = $request->input('type');
        if ($currentLoginWay == 'mobile') {
          $smsCode = Redis::get($request->input('uid'));
          if ($smsCode) {
            if ($smsCode == $request->input('captcha')) {
              $user = User::where('mobile',$request->input('mobile'))->first();
              if(!$user){
                return $this->error("不存在此用户!",[],2);
              }
              $token = Auth::fromUser($user);
            }else{
              return $this->error("验证码错误!");
            }
          }else{
            return $this->error("验证码已过期!");
          }
        }else{
          $validator = Validator::make($request->all(), array(
              'mobile' => 'required',
              'password' => 'required|between:3,20'
          ), array(
              'mobile.required' => '手机号必填',
              'password.required' => '密码必填',
              'password.between' => '密码长度为3到20位'
          ));
          if ($validator->fails()) {
              return $this->error($validator->errors()->first());
          }
          $user = User::where('mobile',$request->input('mobile'))->first();
          if(!$user){
            return $this->error("不存在此用户!",[],2);
          }
          $credentials = request(['mobile', 'password']);
          $token = Auth::guard('api')->attempt($credentials);
          $user = User::where(['password'=>$request->input('password'),'name'=>$request->input('name')])->select("name","type")->first();
        }
        if ($user['password']) {
          unset($user['password']);
        }
        switch ($user['type']) {
          case 0:
            $user['type'] = 'personal';
            break;
          case 1:
            $user['type'] = 'cpuser';
            break;
          default:
            $user['type'] = 'attchuser';
            break;
        }
        return $token?$this->success("登录成功",['token'=>$token,'userInfo'=>$user]):$this->error('密码错误!');
    }

    public function sendcode(Request $request){
      return  $this->send_sms($request->input('uid'),$request->input('mobile'));
    }

    public function resetpwd(Request $request){
      $validatorFiled= array(
        'mobile' => 'required|numeric|regex:/^1[3456789]\d{9}$/',
        'captcha' => 'required',
        'password' => 'required|between:3,20'
      );
      $validatorFiledErorrs = array(
        'captcha.required' => '验证码必填',
        'mobile.required' => '手机号必填',
        'mobile.numeric' => '手机号必须是数字',
        'mobile.regex' => '手机号码格式不对',
        'password.required' => '密码必填',
        'password.between' => '密码长度为3到20位'
      );
      $ddlSql = [
          "mobile" => $request->input('mobile'),
          "password" => Hash::make($request->input('password')),
          "updated_at" => time()
      ];
      $validator = Validator::make($request->all(),$validatorFiled,$validatorFiledErorrs);
      if ($validator->fails()) {
          return $this->error($validator->errors()->first());
      };
      $smsCode = Redis::get($request->input('uid'));
      if ($smsCode) {
        if($smsCode == $request->input('captcha')){
          $data = DB::table("users")->where("mobile",Input::get('mobile'))->first();
          DB::beginTransaction();
          if($data){
              try {
                  DB::table("users")->where('mobile',Input::get('mobile'))->update($ddlSql);
                  DB::commit();
                  return $this->success("重置成功!");
              } catch (\Exception  $th) {
                  $this->log($th);
                  dd($th);
                  DB::rollBack();
                  return $this->error("重置失败");
              }

          }else{
            return  $this->error("用户不存在!");
          }
        }else{
         return  $this->error("验证码错误!");
        }
      }else{
         return  $this->error("验证码已过期!");
      }
    }

    public function register(Request $request){
      $validatorFiled= array(
        'mobile' => 'required|numeric|regex:/^1[3456789]\d{9}$/',
        'captcha' => 'required',
        'password' => 'required|between:3,20'
      );
      $validatorFiledErorrs = array(
        'captcha.required' => '验证码必填',
        'mobile.required' => '手机号必填',
        'mobile.numeric' => '手机号必须是数字',
        'mobile.regex' => '手机号码格式不对',
        'password.required' => '密码必填',
        'password.between' => '密码长度为3到20位'
      );

      $ddlSql = [
          'type' => $request->input('type'),
          "mobile" => $request->input('mobile'),
          "password" => Hash::make($request->input('password')),
          "created_at" => time()
      ];
      if ($request->input('bpname')) {
        $ddlSql = array_merge($ddlSql,['bpname'=> $request->input('bpname')]);
      }
      

      $validator = Validator::make($request->all(),$validatorFiled,$validatorFiledErorrs);
      if ($validator->fails()) {
          return $this->error($validator->errors()->first());
      };
      $smsCode = Redis::get($request->input('uid'));
      if ($smsCode) {
        if($smsCode == $request->input('captcha')){
          $data = DB::table("users")->where("mobile",Input::get('mobile'))->first();
          DB::beginTransaction();
          if(is_null($data)){
              try {
                  DB::table("users")->insert($ddlSql);
                  DB::commit();
                  return $this->success("注册成功!");
              } catch (\Exception  $th) {
                  $this->log($th);

                  DB::rollBack();
                  return $this->error("注册失败");
              }

          }else{
            return  $this->error("手机号重复!");
          }
        }else{
         return  $this->error("验证码错误!");
        }
      }else{
         return  $this->error("验证码已过期!");
      }

    }
    // public function register(Request $request){
    //     $validatorFiled= array(
    //       'mobile' => 'required|numeric|regex:/^1[3456789]\d{9}$/',
    //       'name' => 'required',
    //       'captcha' => 'required',
    //       'password' => 'required|between:3,20'
    //     );
    //     $validatorFiledErorrs = array(
    //       'name.required' => '用户名必填',
    //       'captcha.required' => '验证码必填',
    //       'mobile.required' => '手机号必填',
    //       'mobile.numeric' => '手机号必须是数字',
    //       'mobile.regex' => '手机号码格式不对',
    //       'password.required' => '密码必填',
    //       'password.between' => '密码长度为3到20位'
    //     );
    //     $ddlSql = [
    //         'type' => $request->input('type'),
    //         "mobile" => $request->input('mobile'),
    //         "name" => $request->input('name'),
    //         "password" => Hash::make($request->input('password')),
    //         "created_at" => time()
    //     ];

    //     if($request->input('type') == 0){
    //       $validatorFiled = array_merge($validatorFiled,array(
    //         'idcard' => 'required'
    //       ));
    //       $validatorFiledErorrs = array_merge($validatorFiledErorrs,array(
    //         'idcard.required' => '身份证号必填',
    //         'idcard.regex' => '身份证号格式不对'
    //       ));
    //       $ddlSql =array_merge($ddlSql,[
    //         'idcard' =>$request->input('idcarad')
    //       ]);
    //     }elseif ($request->input('type') == 1) {
    //       $validatorFiled = array_merge($validatorFiled,array(
    //         'bpname' => 'required',
    //         'license' => 'required'
    //       ));
    //       $validatorFiledErorrs = array_merge($validatorFiledErorrs,array(
    //         'bpname.required' => '企业名称必填',
    //         'license.required' => '营业执照格式不对',
    //       ));
    //       $ddlSql =array_merge($ddlSql,[
    //         'bpname' =>$request->input('bpname'),
    //         'license' =>$request->input('license')
    //       ]);
    //     }

    //     $validator = Validator::make($request->all(),$validatorFiled,$validatorFiledErorrs);
    //     if ($validator->fails()) {
    //         return $this->error($validator->errors()->first());
    //     };
    //     $smsCode = Redis::get($request->input('uid'));
    //     if ($smsCode) {
    //       if($smsCode == $request->input('captcha')){
    //         $data = DB::table("users")->where("mobile",Input::get('mobile'))->first();
    //         DB::beginTransaction();
    //         if(is_null($data)){
    //             try {
    //                 DB::table("users")->insert($ddlSql);
    //                 DB::commit();
    //                 return $this->success("注册成功!");
    //             } catch (\Exception  $th) {
    //                 $this->log($th);

    //                 DB::rollBack();
    //                 return $this->error("注册失败");
    //             }

    //         }else{
    //           return  $this->error("手机号重复!");
    //         }
    //       }else{
    //        return  $this->error("验证码错误!");
    //       }
    //     }else{
    //        return  $this->error("验证码已过期!");
    //     }

    // }

    public function getUserInfo(Request $request){
        $userInfo = auth('api')->user();
        unset($userInfo['password']);
        return $this->success("",$userInfo);
    }
    public function updateUser(Request $request){
        $id = auth('api')->user()->id;
        foreach ($request->input() as $key => $value) {
          # code...
        }
       if(User::find($id)->update($request->input())){
        return $this->success();
       }else{
         return $this->error();
       }
  
    }
    public function logout()
    {
        Auth::guard('api')->logout();
        return $this->success("退出成功!");
    }
}
