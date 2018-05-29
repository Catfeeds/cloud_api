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

    public function store()
    {
        return $this->belongsTo(Storemodel::class, 'store_id');
    }

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

    /* 获取当前登陆者拥有权限的城市门店
        {
        "rescode": 0,
        "resmsg": "正确",
        "data": [
            {
                "id": 5,                      登录者的员工id
                "store_ids": "45,46",         所操作的门店id
                "store_names":"福永店,优城店",  所操作的门店名
                "city": [                     所操作门店的所在城市
                    "广州市",
                    "深圳市"
                ]
            }
        ]
    } */
    public static function getMyStores()
    {
        require_once 'Storemodel.php';
        $field = ['id', 'store_ids', 'store_names'];
        //define('CURRENT_ID', 1);
        $employee = static::where('bxid', CURRENT_ID)->get($field)->map(function ($a){
            $store_ids = explode(',', $a->store_ids);
            $storems = Storemodel::whereIn('id', $store_ids)->get(['city'])->map(function ($b){
                return $b->city;
            });
            $a->city =  $storems;
            return $a;
        });
        return $employee;
    }

}
