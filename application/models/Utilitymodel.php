<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/30 0030
 * Time:        18:57
 * Describe:    水电表
 */
class Utilitymodel extends Basemodel
{
    const STATE_PENDING     = 'PENDING';    // 默认
    const STATE_CONFIRM     = 'CONFIRM';    // 确认
    const STATE_COMPLATE    = 'COMPLATE';   // 完成
    const STATE_COMPLETED   = 'COMPLATE';   // 完成

    protected $table        = 'boss_utility';

    protected $fillable     = [
        'status',
    ];

    public function roomtype()
    {
        return $this->belongsTo(Roomtypemodel::class, 'room_type_id');
    }

    public function roomunion()
    {
        return $this->belongsTo(Roomunionmodel::class, 'room_id');
    }

    public function store()
    {
        return $this->belongsTo(Storemodel::class, 'store_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customermodel::class, 'customer_id');
    }
}
