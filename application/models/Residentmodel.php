
<?php
defined('BASEPATH') OR exit('No direct script access allowed');
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
         'room_id','begin_time','people_count','contract_time','discount_id','first_pay_money',
         'deposit_money','deposit_month','tmp_deposit',
         'name','phone','card_type','card_number','card_one','card_two','card_three',
         'name_two','phone_two','card_type_two','card_number_two','address','alternative','alter_phone'
     ];

     protected $hidden  = [];

     //住户的房间信息
     public function roomunion(){

         return $this->belongsTo(Roomunionmodel::class,'room_id');
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





 }
