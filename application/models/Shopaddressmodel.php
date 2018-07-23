<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/2
 * Time:        16:19
 * Describe:    收货地址
 */

class Shopaddressmodel extends Basemodel {
    protected $table  = 'boss_shop_address';
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];
}
