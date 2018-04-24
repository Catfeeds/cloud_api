
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

     protected $table   = 'residents';

     protected $hidden  = [];

     //住户的房间信息
     public function room(){

         return $this->belongsTo(Roommodel::class,'room_id');
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
