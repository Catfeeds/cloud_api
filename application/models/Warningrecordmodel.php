<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/8/28 0028
 * Time:        17:47
 * Describe:    风险预警
 */
class Warningrecordmodel extends Basemodel
{
    protected $table    = 'risk_record';

    const TYPE_LOCKLONG  = 'LOCKLONG'; //长时间未开锁异常
    const TYPE_LOCKERROR = 'LOCKERROR'; //开锁记录异常

    /**
     * 房间
     */
    public function roomunion()
    {
        return $this->belongsTo(Roomunionmodel::class,'room_id');
    }

    /**
     * 住户
     */
    public function resident()
    {
        return $this->belongsTo(Residentmodel::class,'resident_id');
    }

    /**
     * 门店
     */
    public function store()
    {
        return $this->belongsTo(Storemodel::class,'store_id');
    }
}
