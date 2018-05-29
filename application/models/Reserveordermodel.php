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
    protected $fillable = ['visit_by','name','phone','time','work_address','info_source','room_type_id',
        'people_count','check_in_time','guest_type','require','remark'];

    public function employee(){
        return $this->belongsTo(Employeemodel::class,'employee_id')->select('id','name');
    }

    public function roomType(){
        return $this->belongsTo(Roomtypemodel::class,'room_type_id')->select('id','name');
    }
}