<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/20 0020
 * Time:        16:18
 * Describe:    BOSS
 * 门店表
 */

class Storemodel extends Basemodel{

    protected $table    = 'boss_store';

    protected $fillable = [
        'rent_type','status','name','theme','province','city','district','address', 'contact_user',
        'contact_phone','counsel_phone','counsel_time','describe','history','shop','relax','bus',
    ];

    protected $hidden   = ['updated_at','deleted_at'];

    //门店所管辖的楼栋
    public function building(){

        return $this->hasMany(Buildingmodel::class,'store_id');
    }

    //门店的员工信息
    public function employee(){

        return $this->hasMany(Employeemodel::class,'store_id');
    }

    //门店的房型
    public function roomtype(){

        return $this->hasMany(Roomtypemodel::class,'store_id');
    }

    //门店的房间
    public function room(){

        return $this->hasMany(Roommodel::class,'store_id');
    }

}
