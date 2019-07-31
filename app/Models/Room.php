<?php

namespace App\Models;


use App\Models\BaseOrm;
class Room extends BaseOrm
{
  protected $primaryKey='id';
  protected $table = 'room_lists';
  protected $guarded = [];

  public function house(){
    return $this->belongsTo('App\Models\House','houseId','id');
  }


}
