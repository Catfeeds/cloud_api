<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/4/23
 * Time:        10:17
 * Describe:    [boss端]服务订单model
 */
class Serviceordermodel extends Basemodel
{
    protected $table    = 'boss_service_order';
    protected $hidden   = ['created_at','updated_at','deleted_at'];

    public function store()
    {
        return $this->belongsTo(Storemodel::class,'store_id')->select('id','name');
    }
}
