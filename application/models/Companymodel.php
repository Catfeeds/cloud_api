<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Companymodel extends Basemodel
{
    protected $table    = 'fx_company';

    protected $hidden   = [];

    //公司的员工
    public function employee(){

        return $this->hasMany(Employeemodel::class,'company_id');
    }

    //公司的门店
    public function store(){

        return $this->hasMany(Storemodel::class,'company_id');
    }

    //查找公司信息
    public function getInfo($type,$sign){

        switch ($type){
            case 'id':
                $info   = $this->find($sign);
                break;
            case 'phone':
                $info   = $this->where('phone',$sign)->first();
                break;
            case 'wechat':
                $info   = $this->where(WXID,$sign)->first();
                break;
            default:
                $info   = null;
        }
        return $info;
    }
}
