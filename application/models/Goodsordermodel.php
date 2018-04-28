<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/4/28
 * Time:        15:11
 * Describe:    商品管理-商品订单Model
 */
class Goodsordermodel extends Basemodel
{
    protected $table = 'boss_shop_order';
    protected $hidden = ['created_at','updated_at','deleted_at'];
}