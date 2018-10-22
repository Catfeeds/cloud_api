<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      chenkk<cooook@163.com>
 * Date:        2018/6/2
 * Time:        10:17
 * Describe:
 */

class Ownerhousemodel extends Basemodel {

    public function __construct() {
        parent::__construct();
    }

    protected $table = 'boss_owner_house';

    protected $hidden = ['created_at', 'updated_at'];

    protected $fillable = [
        'store_id',             //门店名称
        'layer',                //当前层数
        'area',                 //面积
        'room_count',           //几室
        'hall_count',           //几厅
        'kitchen_count',        //几厨
        'bathroom_count',       //几卫
        'number',               //房间号
        'unit',                 //单元
        'layer_total'           //总楼层
    ];

    public function store(){
        return $this->belongsTo(Storemodel::class,'store_id')->select(['name', 'id', 'city']);
    }

    public function roomunion(){
        return $this->belongsTo(Roomunionmodel::class, 'number', 'number');
    }

    public function owner(){
        return $this->hasOne(Ownermodel::class, 'house_id','id');
    }
}
