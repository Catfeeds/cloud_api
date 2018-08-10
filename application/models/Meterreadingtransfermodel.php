<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/4 0004
 * Time:        14:49
 * Describe:
 */
/**
 * 记录水电费的临时数据，主要用于计算水电费
 */
class Meterreadingtransfermodel extends Basemodel {
    protected $table = 'boss_meter_reading_transfer';

    protected $dates = [];

    protected $fillable = [
        'store_id',
        'building_id',
        'room_id',
        'serial_number',
        'resident_id',
        'year',
        'month',
        'this_time',
        'status',
        'image',
        'weight',
        'type',
        'last_reading',
        'this_reading',
        'confirmed',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'confirmed' => 'boolean',
    ];
//表类型
    const TYPE_WATER_H  = 'HOT_WATER_METER'; //冷水表
    const TYPE_WATER_C  = 'COLD_WATER_METER'; //热水表
    const TYPE_ELECTRIC = 'ELECTRIC_METER'; //电表
    //状态
    const NORMAL    = 'NORMAL'; //正常状态（生成整月账单）
    const OLD_METER = 'CHANGE_OLD'; //旧表
    const NEW_METER = 'CHANGE_NEW'; //新表
    const NEW_RENT  = 'NEW_RENT'; //月中入住
    
    const  UNCONFIRMED  =  0 ;
    const  CONFIRMED    =  1 ;

    /**
     * 该记录所属房间
     */
    public function roomunion() {
        return $this->belongsTo(Roomunionmodel::class, 'room_id');
    }

    public function building() {
        return $this->belongsTo(BuildingModel::class, 'building_id')
            ->select('id', 'name');
    }

    public function store() {
        return $this->belongsTo(Storemodel::class, 'store_id')
            ->select('id', 'name', 'water_price', 'hot_water_price', 'electricity_price');
    }

    public function resident() {
        return $this->belongsTo(Residentmodel::class, 'resident_id')
            ->select('id', 'name', 'customer_id', 'uxid');
    }

    public function room_s() {
        return $this->belongsTo(Roomunionmodel::class, 'room_id')
            ->select('id', 'number');
    }
}
