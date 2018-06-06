<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/2 0002
 * Time:        10:17
 * Describe:
 */
class Devicemodel extends Basemodel
{
    const STATE_PENDING     = 'PENDING';    // 默认
    const STATE_CONFIRM     = 'CONFIRM';    // 确认
    const STATE_COMPLETED   = 'COMPLATE';   // 完成

    protected $table        = 'boss_device';

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
        return $this->belongsTo(Storemodel::class, 'apartment_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customermodel::class, 'customer_id');
    }
}
