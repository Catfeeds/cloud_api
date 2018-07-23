<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/4/27
 * Time:        9:38
 * Describe:    商品管理-商品分类model
 */
class Goodscategorymodel extends Basemodel {
    protected $table  = 'boss_shop_category';
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];
}
