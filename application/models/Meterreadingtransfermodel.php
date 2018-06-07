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
class Meterreadingtransfermodel extends Basemodel
{
    protected $table    = 'boss_meter_reading_transfer';

    protected $dates    = ['created_at', 'updated_at'];

    protected $fillable = [
        'store_id',
        'building_id',
        'room_id',
        'type',
        'last_reading',
        'this_reading',
        'confirmed',
        'created_at',
        'updated_at',
    ];

    protected $casts    = [
        'confirmed' => 'boolean',
    ];

    const TYPE_WATER_H  = 'HOT_WATER_METER';    //冷水表
    const TYPE_WATER_C  = 'COLD_WATER_METER';   //热水表
    const TYPE_ELECTRIC = 'ELECTRIC_METER';     //电表

    const UNCONFIRMED   = 0;
    const CONFIRMED     = 1;

    /**
     * 该记录所属房间
     */
    public function room()
    {
        return $this->belongsTo(Roommodel::class, 'room_id');
    }

    public function building()
    {
        return $this->belongsTo(BuildingModel::class, 'building_id');
    }

    public function apartment()
    {
        return $this->belongsTo(Apartmentmodel::class, 'apartment_id');
    }
}

