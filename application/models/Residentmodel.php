<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/20 0020
 * Time:        16:15
 * Describe:    BOSS
 * 住户表
 */
 class Residentmodel extends Basemodel{

     const CARD_IDCARD = 'IDCARD';               //身份证
     const CARD_OTHER  = 'OTHER';                //其他
     const CARD_ZERO   = '0';                    //身份证
     const CARD_ONE    = '1';                    //护照
     const CARD_TWO    = '2';                    //军人身份证
     const CARD_SIX    = '6';                    //社会保障卡
     const CARD_A      = 'A';                    //武装警察身份证件
     const CARD_B      = 'B';                    //港澳通行证
     const CARD_C      = 'C';                    //台湾居民来往大陆通行证
     const CARD_E      = 'E';                    //户口簿
     const CARD_F      = 'F';                    //临时居民身份证
     const CARD_P      = 'P';                    //外国人永久居留证
     const CARD_YYZZ   = 'BL';                   //营业执照(BUSINESS_LICENSE)

     const STATE_RESERVE         = 'RESERVE';            //预约
     const STATE_NORMAL          = 'NORMAL';             //正常状态
     const STATE_NOTPAY          = 'NOT_PAY';            //未支付
     const STATE_NORMAL_REFUND   = 'NORMAL_REFUND';      //正常退房
     const STATE_UNDER_CONTRACT  = 'UNDER_CONTRACT';     //违约退房
     const STATE_RENEWAL         = 'RENEWAL';            //续租
     const STATE_CHANGE_ROOM     = 'CHANGE_ROOM';        //换房
     const STATE_INVALID         = 'INVALID';            //有缴费订单住户, 未入住, 标记为无效

     const RENTTYPE_SHORT    = 'SHORT';
     const RENTTYPE_LONG     = 'LONG';


     protected $table   = 'web_resident';

     protected $fillable    = [
         'store_id',
         'book_money',
         'book_time',
         'room_id',
        'employee_id',
        'name',
        'phone',
        'card_type',
        'card_number',
        'address',
        'name_two',
        'phone_two',
        'card_type_two',
        'card_number_two',
        'begin_time',
        'end_time',
        'people_count',
        'alternative',
        'alter_phone',
        'special_term',
        'card_one',
        'card_two',
        'card_three',
        'pay_frequency',
        'real_rent_money',
        'real_property_costs',
        'discount_id',
        'first_pay_money',
        'contract_time',
        'rent_type',
        'discount_money',
        'remark',
        'deposit_month',
        'deposit_money',
        'tmp_deposit',
        'status',
        'data',
     ];

     protected $hidden  = [];

     //住户的房间信息
     public function roomunion(){

         return $this->belongsTo(Roomunionmodel::class,'room_id');
     }

     //住户的订单
     public function orders(){

         return $this->hasMany(Ordermodel::class,'resident_id');
     }

     //住户的合同信息
     public function contract(){

         return $this->hasOne(Contractmodel::class,'resident_id');
     }

     //住户的用户信息
     public function customer(){

         return $this->belongsTo(Customermodel::class,'customer_id');
     }

     //同住人信息
     public function commonresident(){

         return $this->hasMany(Commonresidentmodel::class,'resident_id');
     }

     //住户的优惠券
     public function  coupons(){

         return $this->hasMany(Couponmodel::class,'resident_id');
     }

     /**
      * 计算用户的合同结束时间
      * 主要是考虑到, 租房合同开始日期是某个月的月底而结束月份是2月份的情况
      */
     public function contractEndDate($checkInDateStr, $contractTime)
     {
         $checkInDate    = Carbon::parse($checkInDateStr);

         return $this->addMonths($checkInDate, $contractTime);
     }

     /**
      * 计算指定个月后的今天的日期
      * 比如, 1月31日的一个月后可能是2月28号也可能是2月29号
      */
     public function addMonths(Carbon $date, $months = 1)
     {
         $endMonth       = $date
             ->copy()
             ->startOfMonth()
             ->addMonths($months)
             ->endOfMonth();

         if ($endMonth->day >= $date->day - 1) {
             $endTime = $endMonth->startOfMonth()->addDays($date->day - 2);
         }

         return isset($endTime) ? $endTime : $endMonth;
     }








 }
