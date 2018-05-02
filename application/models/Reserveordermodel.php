<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/4/26
 * Time:        15:19
 * Describe:    服务管理-预约订单model
 */

class Reserveordermodel extends Basemodel
{
    protected $table    = 'boss_reserve_order';
    protected $hidden   = ['created_at','updated_at','deleted_at'];

    public function employee()
    {
        return $this->belongsTo(Employeemodel::class,'employee_id')->select('id','name');
    }
}