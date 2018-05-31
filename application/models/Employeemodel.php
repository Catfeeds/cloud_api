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

    //获取当前登陆者拥有权限的某个城市的门店信息
    public static function getMyCitystores($city)
    {
        require_once 'Storemodel.php';
        $employee = static::where('bxid', CURRENT_ID)->get(['store_ids'])->first();
        if (!$employee) return FALSE;
        $store_ids = explode(',', $employee->store_ids);
        $storems = Storemodel::whereIn('id', $store_ids)->where('city', $city)->get(['id', 'name', 'province', 'city', 'district']);
        return $storems;
    }

    //获取当前登陆者拥有权限的某个城市的门店ids
    public static function getMyCitystoreids($city)
    {
        require_once 'Storemodel.php';
        $employee = static::where('bxid', CURRENT_ID)->get(['store_ids'])->first();
        if (!$employee) return FALSE;
        $store_ids = explode(',', $employee->store_ids);
        $stores = Storemodel::whereIn('id', $store_ids)->where('city', $city)->get(['id']);
        if (!$stores) return FALSE;
        foreach($stores as $store) {
            $mystore_ids[] = $store->id;
        }
        return $mystore_ids;
    }

    //获取当前登陆者拥有权限的门店ids
    public static function getMyStoreids()
    {
        $employee = static::where('bxid', CURRENT_ID)->get(['store_ids'])->first();
        if (!$employee) return FALSE;
        $store_ids = explode(',', $employee->store_ids);
        return $store_ids;
    }

    //获取当前登陆者拥有权限的城市
    public static function getMyCities()
    {
        require_once 'Storemodel.php';
        $employee = static::where('bxid', CURRENT_ID)->get(['store_ids'])->first();
        if (!$employee) return FALSE;
        $store_ids = explode(',', $employee->store_ids);
        $cities = Storemodel::whereIn('id', $store_ids)->get(['city'])->map(function ($c){
            return $c->city;
        });
        return $cities;
    }

}
