<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/20 0020
 * Time:        16:13
 * Describe:    BOSS
 * 公司员工表
 */
class Employeemodel extends Basemodel{

    protected $table    = 'boss_employee';

    protected $hidden  = ['created_at','update_at','deleted_at'];

    //员工办理的客户
    public function resident(){

        return $this->hasMany(Residentmodel::class,'employee_id');
    }
    //员工所属的公司
    public function company(){

        return $this->belongsTo(Companymodel::class,'company_id');
    }

    //员公的职位
    public function position(){
        //return $this->select('position_id')->get()->toArray();
        return $this->belongsTo(Positionmodel::class,'position_id');
    }

    //查询员工信息
    public function getInfo($type,$sign){
        switch ($type){
            case 'wechat':
                $info   = $this->where(WXID,$sign)->first();
                break;
            case 'phone':
                $info   = $this->where('phone',$sign)->first();
                break;
            default:
                $info   = null;
        }
        return $info;
    }

}
