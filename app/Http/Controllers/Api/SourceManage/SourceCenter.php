<?php

namespace App\Http\Controllers\Api\SourceManage;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\House;
use Illuminate\Support\Facades\DB;
use App\Models\RentRecord;
class SourceCenter extends Controller
{



   /**
    * [getRoomsFromData description]
    * @param  [type] $roomsData [房源数据]
    * @param  [type] $id        [description]
    * @param  [type] $uuid      [description]
    * @return [type]            [description]
    */
    public function getRoomsFromData ($roomsData,$id){

      return array_values(array_filter($roomsData,function(&$v)use($id){
        //如果是整租
        return $v['houseId'] == $id;
        //如果已出租或预订 查询订单信息
      }));
    }
    /**
     * 获取订单信息
     * @param  Array $rentRecordData
     * @param  int $uuid         house uuid
     * @param  int $id           room id
     * @return Object               返回某一个订单信息
     */
    public function getOrderInfos($rentRecordData,$id = null, $uuid = null){
      // var_dump($id,$uuid);
      $v = array_values(array_filter($rentRecordData,function(&$v)use($id,$uuid){
        if ($id) {
          return $v['uuid'] == $uuid && in_array($id,json_decode($v['room_id']));
        }else{
            return $v['uuid'] == $uuid;
        }

      }));
      if(count($v)){
        $v[0]['rentTimeBegin'] = date('Y/m/d',$v[0]['rentTimeBegin']);
        $v[0]['rentTimeEnd'] = date('Y/m/d',$v[0]['rentTimeEnd']);
      }
   
      return $v;
    }
    /**
     * 按条件查询
     * 一级数据为room
     * 二级数据为house
     */

    public function Index(Request $request){
          //设置查询字段
          $_queryHouse = $_queryRoom = $_inDate = $_Rentdate = [];
          $request->has('communityId')?$_queryHouse[]=['communityId'=>$request->input('communityId')]:null;
          $request->has('projectId')?$_queryHouse[]=['projectId'=>$request->input('projectId')]:null;
          $request->has('rentStatus')?$_queryRoom[]=['rentStatus'=>$request->input('rentStatus')]:null;
          $request->has('Indate')?$_inDate=array(
              $this->formateTime($request->input('Indate')[0]),
              $this->formateTime($request->input('Indate')[1])
          ):null;
          $request->has('Rentdate')?$_Rentdate=array(
              $this->formateTime($request->input('Rentdate')[0]),
              $this->formateTime($request->input('Rentdate')[1])
          ):null;


          $rentRecordData =  (new RentRecord())->getAll([],$_Rentdate);
          $housesData =  (new House())->getAll($_queryHouse,$_inDate);
          $roomsData = (new Room())->getAll($_queryRoom);

          $reponseDATA = []; //temp
          foreach ($housesData as $key => $value) {
            $uuid = $value['uuid'];
            $id   = $value['id'];
            $rentMoney = 0;
            $rentTER = '';
            //相关订单信息
            $currentDcNumber = $value['currentDcNumber'];
            $rooms =$this->getRoomsFromData($roomsData,$value['id']);
            if (!count($rooms)) {
              continue;
            }
            if($value['renttype'] ==='1'){
              //先获取订单信息 只有一个订单信息;
              //整租情况下 只需uuid便可找到相关信息。

             $orderInfo = $this->getOrderInfos($rentRecordData,null,$uuid);
 
             //查看房子状态 整租情况下房间状态一致
             if (count($orderInfo) && $orderInfo[0]['orderStatus'] ==1) {
               //已出租状态
               $rentMoney = $orderInfo[0]['rentMoney'];
               $rentTER   = $orderInfo[0]['name'];
             }else{
               if ($value['crentMoney']) {
                $rentMoney = $value['crentMoney'];
               }
               array_reduce($rooms,function(&$rentMoney,$next){
                 $rentMoney += $next['moneycosts'];
               },0);
             }
              //获取房间信息
              $value['blockName'] = $value['roomNumber'];

              $reponseDATA[$currentDcNumber][] = array_merge($value,array(
                'rooms' => $rooms,
                'roomStatus' => $rooms?$rooms[0]['rentStatus']:null,
                'orderInfo' => $orderInfo?$orderInfo[0]:null,
                'rInfo' => [$rentMoney,$rentTER]
              ));

              //设置租金
            }else{
              //合租
                foreach ($rooms as $k => $v) {

                  $orderInfo = $this->getOrderInfos($rentRecordData,$v['id'],$uuid);
                  

                  $value['blockName'] =((string)$value['roomNumber']).$v['name'];
                  if (count($orderInfo) && $orderInfo[0]['orderStatus'] ==1) {
                    $rentMoney = $orderInfo[0]['rentMoney'];
                    $rentTER   = $orderInfo[0]['name'];
                  }else{
                    $rentMoney = $v['moneycosts'];
                  }
                  $reponseDATA[$currentDcNumber][] = array_merge($value,array(
                    'rooms' => [$v], //合租房间信息只存当前room 一个
                    'roomStatus' => $v['rentStatus'],
                    'orderInfo' => $orderInfo?$orderInfo[0]:null,
                    'rInfo' => [$rentMoney,$rentTER]
                  ));
                }
            }

          }
          return $this->success("",$reponseDATA);

          //rooms
          //getAll
    }
    /**
     * 修改房源
     */
    public function updaterooms(Request $request){
      DB::beginTransaction();
      try {
        foreach($request->input() as $key=>$value){
          $update = $value ;
          $update['roomConfig'] = json_encode($value['roomConfig']);
          $sum = Room::where('id',$key)->update(
            $update
          );
          if($sum === 1 ){
            DB::commit();
          }
        }
        return $this->success('更新成功!');
      } catch (\Exception $th) {
        DB::rollBack();
        return $this->error($th->getMessage());
        //throw $th;
      }
      
    }

    /**
     * 
     */
    public function deleteRent(Request $request)
    {
      DB::beginTransaction();
      try {
       $rooms = json_decode($request->input('room_id'));
       $orderId = $request->input('orderID');
       if(RentRecord::where('orderID','=',$orderId)->delete() == 1 && count($rooms) == Room::destroy($rooms)){ 
        DB::commit();
        return $this->success('删除成功!');
       }else{
        throw new \Exception("删除失败", 1);
       }
       
       
      } catch (\Exception $th) {
        DB::rollBack();
        return $this->error($th->getMessage());
      }
    }


    public function formateTime($time){
      return explode(" ",date('Y-m-d H:i:s',strtotime($time)));
    }
}
