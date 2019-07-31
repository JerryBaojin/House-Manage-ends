<?php

namespace App\Http\Controllers\Api\HouseController;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\Room;
class Index extends Controller
{
    private function _orm($model){
      return $model->select('community_lists.*','fees_config.modelName','house_lists.*')
        ->join('community_lists',function($joinb){
          $joinb->on('community_lists.id','=','house_lists.communityId');
        })->join('fees_config',function($joinb){
          $joinb->on('fees_config.id','=','house_lists.modelId');
        })->get()->toArray();
    }

    public function getAll(){
      $model = new House();
      $datas = $this->_orm($model->with('rooms'));
      $datas[0]['address'] = json_decode($datas[0]['address']);
      return $this->success('',$datas);
    }

    public function getHousesById(Request $request){
      if ($uuid = $request->input('uuid')) {
        $model = new House();
        $datas = $this->_orm($model->with('rooms')->where('uuid',$uuid));
        if(!$datas)return $this->error('ID错误');
        $datas[0]['address'] = json_decode($datas[0]['address']);
        $abbleToDisplay = false;
        foreach ($datas[0]['rooms'] as $key => $value) {
          if ($value['rentStatus'] == 0 ) {
            $abbleToDisplay = true;
            break;
          }
        }
        if ($abbleToDisplay) {
                return $this->success('',$datas);
        }else{
                return $this->error('当前房屋已全部出租或已被预订!');
        }
      }else{
        return $this->error('请输入唯一ID');
      }
    }

    public function provideToService($uuid = null){
        $model = new House();
        $datas = $this->_orm($model->with('rooms')->whereIn('uuid',$uuid));
        $datas[0]['address'] = json_decode($datas[0]['address']);
        $datas[0]['fees'] = json_decode($datas[0]['fees']);
        return $datas[0];

    }
}
