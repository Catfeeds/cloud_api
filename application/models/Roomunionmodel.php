<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/20 0020
 * Time:        16:17
 * Describe:    BOSS
 * 房间信息表(集中式)
 */
class RoomUnionmodel extends Basemodel{

    const HALF = 'HALF';    //合租
    const FULL = 'FULL';    //整租

    protected $table    = 'boss_room_union';

    protected $hidden   = ['created_at','updated_at','deleted_at'];

    //房间所属门店信息
    public function store(){

        return $this->belongsTo(Storemodel::class,'store_id')->select(
            ['id','name','province','city','district','address','describe']);
    }

    //房间所属楼栋信息
    public function building(){

        return $this->belongsTo(Buildingmodel::class,'building_id');
    }

    //房间的合同模板
    public function template(){
        return $this->belongsTo(Contracttemplatemodel::class,'contract_template_id')->select(
            ['id','name']);
    }

    //房间所属房型信息
    public function roomtype(){

        return $this->belongsTo(Roomtypemodel::class,'room_type_id')->select(
            ['id','name','room_number','hall_number','toilet_number','toward','provides','description']);
    }

    //房屋公共智能设备
    public function housesmartdevice(){

        return $this->belongsTo(Smartdevicemodel::class,'house_smart_device_id');
    }

    //房间的智能设备
    public function smartdevice(){

        return $this->belongsTo(SmartDevicemodel::class,'smart_device_id');
    }

    //房间现在的住户信息
    public function resident(){

        return $this->belongsTo(Residentmodel::class,'resident_id');
    }

    //合租人信息
    public function unionresident(){

        return $this->hasMany(Unionresidentmodel::class,'room_id');
    }


}