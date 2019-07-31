<?php

namespace App\Models;
use App\Models\BaseOrm;

class RentRecord extends BaseOrm
{
  protected $table = 'rent_lists';
  public function house(){
    return $this->belongsTo('App\Models\House','uuid','uuid');
  }

  public function getRows($uid,$houseId){
      return ['records'=>$this->where([['uuid',$uuid],['room_id','like',"%".$houseId."%"]])->get()];
  }

}
