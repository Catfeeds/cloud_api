<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/4/26
 * Time:        15:19
 * Describe:    服务管理-预约订单model
 */

class Reserveordermodel extends Basemodel {

    const STATE_WAIT    = 'WAIT';
    const STATE_END     = 'END';

    protected $table    = 'boss_reserve_order';
    protected $hidden   = ['created_at', 'updated_at', 'deleted_at'];
    protected $fillable = ['visit_by', 'name', 'phone', 'time', 'work_address', 'info_source', 'room_type_id',
        'people_count', 'check_in_time', 'guest_type', 'require', 'remark', 'employee_id', 'status'];


    public function employee() {
        return $this->belongsTo(Employeemodel::class, 'employee_id')->select('id', 'name');
    }

    public function roomType() {
        return $this->belongsTo(Roomtypemodel::class, 'room_type_id')->select('id', 'name');
    }

    public function store(){
        return $this->belongsTo(Storemodel::class,'store_id')->select('id','name');
    }

    public function taskflow()
    {
        return $this->belongsTo(Taskflowmodel::class,'taskflow_id');
    }

    /*
     * 判断状态
     * */
    public function is_reserve($status){
        switch($status){
            case 'BEGIN'        : $res = '开始';break;
            case 'WAIT'         : $res = '等待';break;
            case 'INVALID'      : $res = '失效';break;
            case 'END'          : $res = '结束';break;
            default             : $res ='';
        }
        return $res;
    }
    /*
     * 判断来访类型
     * */
    public function is_visit_by($status){
        switch($status){
            case 'PHONE'        : $res = '电话咨询';break;
            case 'VISIT'        : $res = '现场看房';break;
            case 'WEB'          : $res = '官网看房';break;
            case 'WECHAT'       : $res = '订房系统预约';break;
            default             : $res ='';
        }
        return $res;
    }
}
