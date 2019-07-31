<?php

namespace App\Http\Controllers\Api\SourceManage;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\House;
use App\Models\Room;

class SourceConfig extends Controller
{
    public function uuid(){
        $maxId = (new House())->max('id');
        return  substr(time() . str_pad(mt_rand(1,999),3, '0', STR_PAD_LEFT) . intval($maxId),1,9);
    }
    public function getProject(Request $request){
      $projectDatas = DB::table("pro_lists")->where('uid',$this->getuserId())->get()->toArray();
      return $this->success("",$projectDatas);
    }
    public function getCommunity(Request $request){
      $communityDatas = DB::table("community_lists")->where('uid',$this->getuserId())->get()->toArray();
      return $this->success("",$communityDatas);
    }

    public function addCommunity(Request $request){

      $modelData = DB::table("community_lists")->where(["uid"=>$this->getuserId(),'communityName'=>$request->input('communityName')])->get()->toArray();
      if(count($modelData) !== 0){
        return $this->error("小区名重复!");
      }
      try {
        $newInfo = DB::table("community_lists")->insertGetId([
          'address'=>json_encode($request->input('address')),
          'detailAddr'=>$request->input('detailsAddress'),
          'communityName'=>$request->input('communityName'),
          'uid'=>$this->getuserId()
        ]);

        return $this->success("添加成功!",array_merge($request->input(),['id'=>$newInfo,'uid'=>$this->getuserId()]));
      } catch (\Exception $e) {
          return $this->error("添加失败!",$e->getmessage());
      }
    }

    //
    public function addProject(Request $request){

      if (!$request->input('ProjectName')) {
        return  $this->error("请填写项目名称!");
      }
      $hasData = DB::table('pro_lists')->where(["uid"=>$this->getuserId(),'ProjectName'=>$request->input('ProjectName')])->get()->toArray();
      if(count($hasData) !== 0){
        return $this->error("项目名重复!");
      }
        $res = DB::table("pro_lists")->insertGetId([
          "uid"=>$this->getuserId(),
          "ProjectName"=>$request->input('ProjectName')
        ]);
        return $this->success('',['id'=>$res,'ProjectName'=>$request->input('ProjectName')]);
    }

    public function deleteProject(Request $request){
      if (!$request->input('id')) {
        return  $this->error("请填写id!");
      }
      DB::beginTransaction();
      try {
        $res = DB::table('pro_lists')->where('id',$request->input('id'))->delete();
        if (!$res) {
          throw new \Exception("删除失败", 1);
        }
        DB::commit();
        return $this->success('删除成功!');
      } catch (\Exception $e) {
          DB::rollBack();
          return $this->error("删除失败!");
      }
    }

    public function deleteCommunity(Request $request){
      if (!$request->input('id')) {
        return  $this->error("请填写id!");
      }
      DB::beginTransaction();
      try {
        $res = DB::table('community_lists')->where('id',$request->input('id'))->delete();
        if (!$res) {
          throw new \Exception("删除失败", 1);
        }
        DB::commit();
        return $this->success('删除成功!');
      } catch (\Exception $e) {
          DB::rollBack();
          return $this->error("删除失败!");
      }

    }

    public function submitSourceHouse(Request $request){
      $houseModel = new House;
      $RoomModel = new Room;
      try {
        foreach ($request->except(['values','fees','data']) as $key => $value) {
          $houseModel->$key = $value;
        };
        $houseModel->houseKeepingStart = strtotime($request->input('data')[0]);
        $houseModel->houseKeepingEnd = strtotime($request->input('data')[1]);
        $houseModel->uid = $this->getuserId();
        $houseModel->uuid = $this->uuid();

        $houseModel->fees = json_encode($request->input('fees'));
        if ( array_key_exists("publicConfig",$request->input('values'))) {

          $houseModel->publicConfig = json_encode($request->input('values')['publicConfig']);
        }
        $houseModel->save();
        $id = $houseModel->id;
        $rooms = $request->input('values')['FORM'];

        foreach ( $rooms as $key => &$value) {
          $value['created_at'] = date('Y-m-d H:i:s',time());
          $value['uid'] = $this->getuserId();
          $value['houseId'] = $id;
          $value['roomConfig'] =  array_key_exists('roomConfig',$value)?json_encode($value['roomConfig']):'[]';
        }
        $roomResponse =  $RoomModel->insert($rooms);
        if ($roomResponse ) {
          return $this->success("添加成功!");
        }else{
          throw new \Exception("添加失败", 1);

        }
      } catch (\Exception $e) {
        $this->log($e);
        return $this->error($e->getMessage());
      }



    }
}
