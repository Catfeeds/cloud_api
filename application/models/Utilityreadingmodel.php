<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/4 0004
 * Time:        14:51
 * Describe:
 */
class Utilityreadingmodel extends Basemodel
{
    protected $table    = 'boss_utility_reading';

    protected $dates    = ['created_at', 'updated_at'];

    protected $fillable = [
        'order_id',
        'start_reading',
        'end_reading',
        'weight',
        'created_at',
        'updated_at',
    ];

    /**
     * 该记录所属房间
     */
    public function order()
    {
        return $this->belongsTo(Ordermodel::class, 'room_id');
    }

}