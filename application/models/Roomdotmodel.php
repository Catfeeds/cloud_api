<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/7 0007
 * Time:        11:56
 * Describe:    房间信息（分布式）
 */
class Roomdotmodel extends Basemodel {
    const HALF = 'HALF';    //合租
    const FULL = 'FULL';    //整租

    protected $table    = 'boss_room_dot';

    protected $fillable = ['store_id','community_id','house_id','number','area','toward','feature','provides',
        'contract_template_id','contract_min_time','contract_max_time','deposit_type','pay_frequency_allow',];

    protected $hidden   = ['created_at','updated_at','deleted_at'];

    //房间所属门店信息
    public function store(){

        return $this->belongsTo(Storemodel::class,'store_id')->select(
            ['id','name','province','city','district','address','describe']);
    }

    //房间所属小区信息
    public function community(){
        return $this->belongsTo(Communitymodel::class,'community_id')->select(['id','name']);
    }

    //房间所属房屋house信息
    public function house(){
        return $this->belongsTo(Housemodel::class,'house_id')->select(
            ['id','building_name','unit','layer','layer_total','number','room_number','hall_number','toilet_number']
        );
    }

    //房间的长租合同模板
    public function long_template(){
        return $this->belongsTo(Contracttemplatemodel::class,'contract_template_long_id')
            ->where('rent_type','LONG')->select(['id','name']);
    }
    //房间的短租合同模板
    public function short_template(){
        return $this->belongsTo(Contracttemplatemodel::class,'contract_template_short_id')
            ->where('rent_type','SHORT')->select(['id','name']);
    }
    //房间的预定合同模板
    public function reserve_template(){
        return $this->belongsTo(Contracttemplatemodel::class,'contract_template_reserve_id')
            ->where('rent_type','RESERVE')->select(['id','name']);
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
