<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/24 0024
 * Time:        16:47
 * Describe:    优惠券
 */
class Couponmodel extends Basemodel
{
    /**
     * 用户优惠券的状态
     */
    const STATUS_ASSIGNED       = 'ASSIGNED';      //已分配的状态, 适用于吸粉活动的优惠券
    const STATUS_UNUSED         = 'UNUSED';        //未使用
    const STATUS_ROLLBACKING    = 'ROLLBACKING';   //订单取消之后的优惠券回滚状态
    const STATUS_OCCUPIED       = 'OCCUPIED';      //订单支付且未确认时优惠券的占用状态
    const STATUS_USED           = 'USED';          //已使用
    const STATUS_EXPIRED        = 'EXPIRED';       //未使用且过期
    protected $table    = 'boss_coupon';

    protected $fillable = [
        'status',
        'deadline',
        'activity_id',
        'customer_id',
        'resident_id',
        'order_id',
        'coupon_type_id'
    ];



}
