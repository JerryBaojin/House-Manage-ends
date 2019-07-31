<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseOrm extends Model
{


  public static function boot()
  {
    parent::boot();

    self::creating(function($model){
       $model->uid =  auth('api')->user()->id;

       return $model;
    });
    self::saving(function($model){
       $model->uid = auth('api')->user()->id;

       return $model;
    });

  }

  public function scopeFetchdata($query)
  {
      return $query->where($this->table.'.uid','=', auth('api')->user()->id);
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
      })->get()->toArray();
  }
}
