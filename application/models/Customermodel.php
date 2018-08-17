<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/20 0020
 * Time:        16:11
 * Describe:    [FUNX][BOSS]
 * 用户信息表
 */

class Customermodel extends Basemodel {

    protected $table = 'boss_customer';

    protected $hidden = [];

    public function coupons() {
        return $this->hasMany(Couponmodel::class, 'customer_id');
    }
}
