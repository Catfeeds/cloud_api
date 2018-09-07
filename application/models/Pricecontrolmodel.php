<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/8/16 0016
 * Time:        10:28
 * Describe:    调价
 */
class Pricecontrolmodel extends Basemodel
{
    const TYPE_ROOM       = 'ROOM';     //调价类型 房租
    const TYPE_MANAGEMENT = 'MANAGEMENT';//调价类型 物业费
    const TYPE_ELECTRICITY= 'ELECTRICITY';//调价类型 电费
    const TYPE_WATER      = 'WATER';    //调价类型 水
    const TYPE_HOTWATER   = 'HOTWATER';//调价类型 热水
    const STATE_AUDIT     = 'AUDIT';    //调价状态 审核中
    const STATE_DONE      = 'DONE';    //调价状态 调价完成
    const STATE_CLOSED    = 'CLOSED';   //调价状态 调价关闭

    protected $table = 'boss_price_control';

    public function taskflow()
    {
        return $this->belongsTo(Taskflowmodel::class,'taskflow_id');
    }

    public function store()
    {
        return $this->belongsTo(Storemodel::class,'store_id');
    }

    public function roomunion()
    {
        return $this->belongsTo(Roomunionmodel::class,'room_id');
    }

}
