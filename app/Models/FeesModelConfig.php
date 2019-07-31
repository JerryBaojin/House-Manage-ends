<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class FeesModelConfig extends Model
{
    protected $primaryKey='id';
    protected $table = 'fees_config';
    protected $fillable = ['model','uid', 'modelName'];
    public $timestamps = false;
    public function _modelAdd($data)
    {
        return $this->insertGetId($data);
    }
    //单条查找
    public function getfind($id)
    {
        if($this->where('id',$id)->first()){
          return $this->where('id',$id)->first()->toArray();
        }else{
          return [];
        }
    }
    //查询用户有几个uid,返回数量
    public function countCity($uid){
        if($this->where('uid',$uid)->first()){
          return $this->where('uid',$uid)->count();
        }else{
          return [];
        }
    }
    //查询全部数据
    public function getAll()
    {
        return $this->get()->toArray();
    }
    /**
    * 修改管理员信息
    * @param $id
    * @param $data
    * @return bool
    */
    public function upAdmin($id,$data)
    {
        if($this->find($id)){
          return $this->where('id',$id)->update($data);
        }else{
          return false;
        }
    }

    /**
    * 根据id查找城池信息 只返回某个字段的值
    * @param $id
    * @return array
    */
    public function getCityName($id)
    {
        if($this->where('city_id',$id)->first()){
          return $this->where('city_id',$id)->lists('city_name')[0];
        }else{
          return [];
        }
    }
}
