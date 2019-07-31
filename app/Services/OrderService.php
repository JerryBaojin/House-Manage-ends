<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\RentRecord;
use App\Models\Room;
use Illuminate\Support\Facades\Validator;


/**
 *
 */
class OrderService extends BaseSerivce
{
  /**
     * 生成一个新的订单号
     * 时间日期 + 3随机数 + 当前订单主键最大值（由于订单编号中有总订单的个数，所以一般不会有重复的）
     * @return [type] [description]
     */
  protected $RentModel;
  public function __construct()
  {
    $this->RentModel = new RentRecord();
  }
  public function build_order_sn(){

      $maxId = $this->RentModel->max('id');
      return date('Ymd') . str_pad(mt_rand(1,999),3, '0', STR_PAD_LEFT) . intval($maxId);
  }
  public function updatePreOrder($request){
    $validator = Validator::make($request->input(), array('orderID' =>'required','render' => 'required', 'houseNo' => 'required','rentInfo'=>'required','idcardImgs'=>'required'));
    if ($validator->fails()) {
        return $this->error('请填写完整信息');
    }
    return  $this->saveOrder($request,$request->input('orderID'));
  }
  public function addOrder($request){


    $validator = Validator::make($request->input(), array('render' => 'required', 'houseNo' => 'required','rentInfo'=>'required','idcardImgs'=>'required'));
    if ($validator->fails()) {
        return $this->error('请填写完整信息');
    }


    return  $this->saveOrder($request);
  }
  public function saveOrder($request,$_orederID = null){
        if ($this->hasEffectsRows($request->input('houseNo')) === 0) {
            return $this->error('房间当前状态不允许操作!');
        }
          try {
            $orderId = $_orederID?$_orederID:$this->build_order_sn();
            $model = $_orederID?RentRecord::fetchdata()->where('orderID',$_orederID)->first():$this->RentModel;
            $eachData = $request->except(['fees','ext','idcardImgs','houseNo']);
            if ($_orederID) {
        //      $model = RentRecord::fetchdata()->where('orderID',$_orederID);
              unset($eachData['orderID']);
            }
            $result = [];

            foreach ($eachData as $key => $value) {
                foreach ($value as $k => $val) {
                  if ($k == 'data' && count($val)!== 0) {
                    $model->rentTimeBegin = strtotime($val[0]);
                    $model->rentTimeEnd = strtotime($val[1]);
                  }else{
                    $model->$k = $val;
                  }
                };
            }
            $model->afixFees = json_encode($request->input('fees')['afixFees']);
            $model->computedFees = json_encode($request->input('fees')['computedFees']);
            $model->ext = json_encode($request->input('ext'));
            $model->idcardImgs = json_encode($request->input('idcardImgs'));
            //$model->uid = auth('api')->user()->id;
            $model->orderID = $orderId;
            $model->orderStatus = 1;
            $model->room_id = json_encode($request->input('houseNo'));
            DB::beginTransaction();
            $res = $model->save();
            if ($res) {
                //更新room 置status为已出租状态
                $rowEffects = Room::whereIn('id',$request->input('houseNo'))->update(['rentStatus'=>1]);

                if ($rowEffects!==count($request->input('houseNo'))) {
                  throw new Exception("保存失败", 1);
                }else{
                  DB::commit();
                  return $this->success("添加成功!");

                }
            }else{
              throw new Exception("保存失败", 1);
            }
          } catch (\Exception $e) {
            DB::rollBack();
            $this->log($e);
            return $this->error($e->getMessage());
          }


  }

  public function addPreOrder($request){
    $validator = Validator::make($request->input(), array('render' => 'required', 'houseNo' => 'required','rentInfo'=>'required'));
    if ($validator->fails()) {
        return $this->error('请填写完整信息');
    }

    try {
      $orderId = $this->build_order_sn();
      $model = new RentRecord();
      $result = [];
      foreach ($request->except(['houseNo']) as $key => $value) {
          foreach ($value as $k => $val) {
            $model->$k = $val;
          }
      }
      $model->uid = auth('api')->user()->id;
      $model->orderID = $orderId;
      $model->orderStatus = 0;
      $model->room_id = json_encode($request->input('houseNo'));
      DB::beginTransaction();
      $res = $model->save();
      if ($res) {
          //更新room 置status为已出租状态
          $rowEffects = Room::whereIn('id',$request->input('houseNo'))->update(['rentStatus'=>2]);
          if ($rowEffects!==count($request->input('houseNo'))) {
            throw new Exception("保存信息失败", 1);
          }else{
            DB::commit();
            return $this->success("添加预订成功!");
          }
      }else{
        throw new Exception("保存失败", 1);
      }
    } catch (\Exception $e) {
      DB::rollBack();
      $this->log($e);
      return $this->error($e->getMessage());
    }


  }

  public function queryOrderByOrderId($id){
    return  RentRecord::fetchdata()->where('orderID',$id)->get();
  }
  public function queryOrderByAll(){
    return  RentRecord::fetchdata()->get();
  }
  public function hasEffectsRows($houseNo = []){
    return Room::where('rentStatus','<>',"1")->whereIn('id',$houseNo)->count();
  }
}
