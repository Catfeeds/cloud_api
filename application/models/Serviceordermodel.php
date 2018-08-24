<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/4/23
 * Time:        10:17
 * Describe:    [boss端]服务订单model
 */
class Serviceordermodel extends Basemodel {
    protected $table    = 'boss_service_order';
    protected $hidden   = ['created_at', 'updated_at', 'deleted_at'];
    protected $fillable = ['room_id', 'time', 'name', 'phone', 'type', 'money', 'remark', 'addr_from', 'addr_to'];

    public function store() {
        return $this->belongsTo(Storemodel::class, 'store_id')->select('id', 'name');
    }
    public function serviceType() {
        return $this->belongsTo(Servicetypemodel::class, 'service_id')->select('id', 'name');
    }

    public function roomunion() {
        return $this->belongsTo(Roomunionmodel::class, 'room_id');
    }
    public function customer() {
        return $this->belongsTo(Customermodel::class, 'customer_id');
    }

    public function getOrderNumber() {
        return date('YmdHis') . mt_rand(1000000000, 9999999999);
    }

    public function taskflow()
    {
        return $this->belongsTo(Taskflowmodel::class,'taskflow_id');
    }
}
