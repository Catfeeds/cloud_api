<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/4/27
 * Time:        10:32
 * Describe:
 */
class Goodsmodel extends Basemodel
{
    protected $table = 'boss_shop_goods';
    protected $hidden = ['created_at','updated_at','deleted_at'];
}