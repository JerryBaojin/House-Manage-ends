<?php

namespace App\Models;

use App\Models\BaseOrm;

use Illuminate\Support\Facades\DB;
class House extends BaseOrm
{
    protected $table = 'house_lists';
    protected $fillable = ['uid', 'pid'];


    public function filterModel($uuid = null){
      if ($uuid) {
          return $this->where('house_lists.uuid','=',$uuid);
      }
      return $this->where('house_lists.uid','=',auth('api')->user()->id);
    }

    public function rooms(){
      return $this->hasMany('App\Models\Room','houseId','id');
    }

    public function renRecords(){
      return $this->hasMany('App\Models\RentRecord','uuid','uuid');
    }
    //查询全部数据
    public function getAll($params = [],$time = [])
    {

        return $this->fetchdata()->where(function($query)use($params,$time){

          if (count($params)) {

            foreach ($params as $key => $value) {
              $query->orWhere($value);
            }
          }
          if (count($time)) {
            $query->whereDate('created_at', '>=', $time[0][0])
             ->whereDate('created_at', '<=', $time[1][0]);
          }
        })->select('community_lists.*','pro_lists.*','fees_config.modelName','house_lists.*')
          ->leftjoin('community_lists',function($joinb){
            $joinb->on('community_lists.id','=','house_lists.communityId');
          })->leftjoin('fees_config',function($joinb){
            $joinb->on('fees_config.id','=','house_lists.modelId');
          })->leftjoin('pro_lists',function($joinb){
            $joinb->on('pro_lists.id','=','house_lists.projectId');
          })->get()->toArray();
    }
}
