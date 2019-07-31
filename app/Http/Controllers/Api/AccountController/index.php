<?php

namespace App\Http\Controllers\Api\AccountController;

use Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class Index extends Controller
{

    private function findDataByTime($begin, $end)
    {

      $res = DB::table("account_lists")
                  ->where( 'uid', auth('api')->user()->id )
                  ->whereBetween('date', [$begin, $end])->get()->toArray();

      return $res;
    }

    /**
     *  获取指定时间的 支出、 收入、 累计金额
     * @param $begin
     * @param $end
     * @return array 
     */
    private function findAccountByTime($begin = null, $end = null)
    {

      if ($begin == null || $end == null) {
        // 累计收入
        $sr = DB::table("account_lists")
                    ->where( 'uid', auth('api')->user()->id )
                    ->where('owntype', 'sr')->sum('money');

        // 累计支出
        $zc = DB::table("account_lists")
                    ->where( 'uid', auth('api')->user()->id )
                    ->where('owntype', 'zc')->sum('money');
                    
        return ['sr' => (string)$sr, 'zc' => (string)$zc];  
      }
      // 收入
      $sr = DB::table("account_lists")
                  ->where( 'uid', auth('api')->user()->id )
                  ->whereBetween('date', [$begin, $end])->where('owntype', 'sr')->sum('money');

      // 支出
      $zc = DB::table("account_lists")
                  ->where( 'uid', auth('api')->user()->id )
                  ->whereBetween('date', [$begin, $end])->where('owntype', 'zc')->sum('money');

      return ['sr' => (string)$sr, 'zc' => (string)$zc];  
    }

    /**
     * 添加 账务 api
     */
    public function addOrder(Request $request)
    {
     
      $account_data = $request->all();
      $way = array_key_exists('srlx', $account_data) ? 'srlx' : 'zclx';
      $account_data['way'] = $account_data[$way];
      unset($account_data[$way]);

      try {

        // 验证数据的合法性
        $validator = Validator::make($account_data, $this->vaild($account_data), $this->msg());

        if ($validator->fails()) {

          $msg = $validator->errors()->first();
          return $this->error($msg);
        }

        return $this->addAccountData($account_data) ? $this->success('添加成功') : $this->error('系统繁忙');
      } 
      catch(\Exception $e) {

        return $this->error($e->getMessage());
      }
    }


    /**
     * 查询累计和本月支出 收入 api
     */
    public function allOrder(Request $request)
    {

      $type = $request->input('_type');

      try {
        switch ($type) {
          case 'all':

            $result = $this->findAccountByTime();
            break;
          case 'month':

            // 本月第一天
            $BeginDate=date('Y-m-01 00:00:00', strtotime(date("Y-m-d")));
            // 本月最后一天
            $endDate=date('Y-m-d 23:59:59', strtotime("$BeginDate +1 month -1 day"));

            $result = $this->findAccountByTime(strtotime($BeginDate), strtotime($endDate));
            break;
          default:
            // 根据用户选择时间 来查询
            $begin = $request->input('begin');
            $end   = $request->input('end');

            $result = $this->findAccountByTime(strtotime($begin), strtotime($end));
            break;
        }

        return $this->success('success', $result);

      }catch(\Exception $e) {

        return $this->error($e->getMessage().'-'.$e->getLine());
      }
    }

    /**
     * 获取 table 数据
     */
    public function account_list(Request $request)
    {

      $page   = $request->input('_page');
      $limit  = $request->input('_limit');
      $type   = $request->input('_type');

      $param  = ($page - 1) * $limit;
      try{
        $uid = auth('api')->user()->id;
        $data = array();

        if ($type == 'sr' || $type == 'zc') {

          $result = DB::table('account_lists')->where( 'uid', $uid )->where('owntype', '=', $type)->orderBy('id', 'desc')->skip($param)->take($limit)->get();
          $count = DB::table('account_lists')->where( 'uid', $uid )->where('owntype', '=', $type)->count();

          $data['data'] = $result;
          $data['total'] = $count;
        } else {

          $result = DB::table('account_lists')->where('uid', $uid)->orderBy('id', 'desc')->skip($param)->take($limit)->get();
          $count = DB::table('account_lists')->where('uid', $uid)->count();
          
          $data['data'] = $result;
          $data['total'] = $count;
        }

        return $this->success('success', $data);

      } catch(\Exception $e) {

        return $this->error($e->getMessage());
      }

    }

    /**
     * 删除
     */
    public function deleteById($id)
    {

      try{

        $res = DB::table('account_lists')->where('id', '=', $id)->delete();

        return $res ? $this->success('刪除成功') : $this->error('删除失败');

      }catch(\Exception $e) {

        return $this->error($e->getMessage());
      }
    }

    /**
     * 添加账单信息
     * @param Array $account_data
     * @return bool
     */
    protected function addAccountData($account_data){

      $account_data['created_at'] = date('Y-m-d H:i:s');
      $account_data['updated_at'] = date('Y-m-d H:i:s');

      $account_data['date'] = strtotime($account_data['date']);
      $account_data['uid'] = auth('api')->user()->id;
      $id = DB::table("account_lists")->insertGetId($account_data);

      return $id ? true : false;

    }


    protected function vaild($bool)
    {

      return [
          'owntype'   => "required | in:sr,zc",
          'project'   => 'required',    // 项目
          'community' => 'required',  // 小区
          'date'      => 'date',       // date
          'skfs'      => 'required',
          'handle'    => 'required',
          'remark'    => 'required',
          'way'       => 'required',
        ];
    }

    protected function msg()
    {

      return [
        'owntype.in' => '方式错误,必须为收入或支出.',
        'required'    => 'The :attribute required.',
        'size'    => 'The :attribute must be exactly :size.',
        'between' => 'The :attribute must be between :min - :max.',
        'in'      => 'The :attribute must be one of the following types: :values',
      ];
    }
}
