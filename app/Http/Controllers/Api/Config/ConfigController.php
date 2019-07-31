<?php

namespace App\Http\Controllers\Api\Config;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\FeesModelConfig;
use App\Models\User;
class ConfigController extends Controller
{


    public function deleteConfig(Request $request){
      if ($datas = FeesModelConfig::where("id",$request->input('id'))->delete()) {
        return $this->success("",$datas);
      }else{
        return $this->error("删除失败",$datas);
      }

    }

    public function updateConfig(Request $request){
      if ($datas = FeesModelConfig::where(["id"=>$request->input('id'),"uid"=>$this->getuserId()])->update(['modelName'=>$request->input('modelName'),'model'=>json_encode($request->except(['id','modelName']))])) {
        return $this->success("",$datas);
      }else{
        return $this->error("修改失败",$datas);
      }
    }

    public function getCurrentAllData(Request $request){
      $datas = FeesModelConfig::where("uid",$this->getuserId())->get();
        return $this->success("",['data'=>$datas]);
    }

    public function addFeesConfigModel(Request $request){
      $modelData = FeesModelConfig::where(["uid"=>$this->getuserId(),'modelName'=>$request->input('modelName')])->get()->toArray();
      if(count($modelData) !== 0){
        return $this->error("模板名重复!");
      }

      if($newInfo = FeesModelConfig::create(['model'=>json_encode($request->except(['id','index','modelName'])),'modelName'=>$request->input('modelName'),'uid'=>$this->getuserId()])){
        return $this->success("添加成功!",$newInfo);
      }
    }



}
