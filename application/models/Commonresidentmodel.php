<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/20 0020
 * Time:        16:19
 * Describe:    BOSS
 * 同住人信息
 */
class Commonresidentmodel extends Basemodel{

    protected $table    = 'common_residents';

    protected $hidden   = [];

    //合租人的用户信息
    public function customer(){

        return $this->belongsTo(Customermodel::class,'customer_id');
    }

    //合租人的主租人
    public function resident(){

        return $this->belongsTo(Residentmodel::class,'resident_id');
    }
}