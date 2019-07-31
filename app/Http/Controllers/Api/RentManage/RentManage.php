<?php

namespace App\Http\Controllers\Api\RentManage;

use App;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\RentRecord;
use App\Http\Controllers\Api\HouseController\Index;
use App\Services\OrderService;

class RentManage extends Controller
{
    private $_order_service;
    public function __construct(OrderService $order){
       $this->_order_service = $order;
    }

    public function add(Request $request){
      $service = App::make('order');
      return $this->_order_service->addOrder($request);
    }
    public function addRenter(Request $request){
      $service = App::make('order');
      return $this->_order_service->addPreOrder($request);
    }

     public function updateOrder(Request $request){
        return $this->_order_service->updatePreOrder($request);
    }
    /*
    默认只查询一条数据
    */

    public function getOrder(Request $request){
  //    ($orderID = $request->input('orderID'))?$datas = $this->_order_service->queryOrderByOrderId($orderID):$datas = $this->_order_service->queryOrderByAll();
      if ($orderID = $request->input('orderID')) {
        $datas = $this->_order_service->queryOrderByOrderId($orderID);
        $datas[0]['room_id'] = json_decode($datas[0]['room_id']);
        $housetController  = new Index();
        $uuid = [];
        foreach ($datas as $key => $value) {
          $uuid[] = $value['uuid'];
        }
        $house =  $housetController->provideToService($uuid);
        $house['rooms'] = array_filter($house['rooms'],function($v)use($datas){
          return in_array($v['id'],$datas[0]['room_id']);
        });
        return $this->success('',['houseData'=>$house,'preOrderInfo'=>$datas[0]]);
      }else{
        return $this->error('缺少orderID');
      }

    }
}
