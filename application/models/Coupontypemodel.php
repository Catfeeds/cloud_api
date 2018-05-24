<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/24 0024
 * Time:        16:47
 * Describe:    优惠券类型
 */
class Coupontypemodel extends Basemodel
{
    protected $table        = 'boss_coupon_type';

    protected $fillable     = ['name', 'description', 'limit', 'deadline', 'valid_time', 'type', 'status', 'discount'];

    /**
     * 优惠券类型
     */
    const TYPE_CASH         = 'CASH';       //代金券
    const TYPE_DISCOUNT     = 'DISCOUNT';   //折扣券
    const TYPE_REMIT        = 'REMIT';      //减免券

    /**
     * 使用限制
     */
    const LIMIT_ROOM        = 'ROOM';           //支付房租
    const LIMIT_UTILITY     = 'UTILITY';        //支付水电费
    const LIMIT_DEVICE      = 'DEIVCE';         //支付设备费用, 原来的就错了, 没办法, 兼容吧
    const LIMIT_SERVICE     = 'MANAGEMENT';     //支付服务管理费

    /**
     * 优惠券的状态
     */
    const STATUS_GENERATED  = 'GENERATED';       //已经生成
    const STATUS_ACTIVATED  = 'ACTIVATED';       //已经激活, 有人领取的状态
    const STATUS_FINISHED   = 'FINISHED';        //活动已经结束, 不能再领取
    const STATUS_ABANDONED  = 'ABANDONED';       //被删除了, 不为外人道也
}
