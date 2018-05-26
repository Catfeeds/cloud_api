<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/25 0025
 * Time:        9:45
 * Describe:
 */
class Activitymodel extends Basemodel
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    protected $table    = 'boss_activity';

    protected $fillable = ['name', 'description'];

    protected $dates    = ['created_at', 'updated_at', 'start_time', 'end_time'];

    /**
     * 活动类型
     */
    const TYPE_ATTRACT  = 'ATTRACT';     //吸粉活动
    const TYPE_NORMAL   = 'NORMAL';      //普通活动, 先不管, 先处理吸粉活动
    const TYPE_DISCOUNT = 'DISCOUNT';    //房租打折

    /**
     * 参与该活动的公寓
     */
//    public function apartments()
//    {
//        return $this->belongsToMany(Apartment::class, 'apartment_activity', 'activity_id', 'apartment_id');
//    }

    /**
     * 获取该活动相关的优惠券类型
     */
    public function coupontypes()
    {
        return $this
            ->belongsToMany(
                Coupontypemodel::class, 'boss_activity_coupontype', 'activity_id', 'coupon_type_id'
            )
            ->withPivot('count', 'min');
    }

    public function coupons()
    {
        return $this->hasMany(Couponmodel::class, 'activity_id');
    }

//    public function helprecords()
//    {
//        return $this->hasMany(Helprecord::class, 'activity_id');
//    }

}