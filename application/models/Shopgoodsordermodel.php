<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/2
 * Time:        17:28
 * Describe:    商品-订单关联
 */

class Shopgoodsordermodel extends Basemodel
{
    protected $table = 'boss_shop_order_goods';
    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function shopOrder(){
        return $this->belongsTo(Goodsordermodel::class,'order_id')->select('id');
    }
    public function goodsName()
    {
        return $this->belongsTo(Goodsmodel::class, 'goods_id')->select('id','name');
    }
}