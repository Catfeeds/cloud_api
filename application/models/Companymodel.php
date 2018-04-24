<?php

/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/4/12
 * Time:        13:14
 * Describe:    [FUNX/BOSS]
 * 公司表
 */

class Companymodel extends Basemodel
{
    protected $table    = 'fx_companies';

    protected $hidden   = [];

    //公司的员工
    public function employee(){

        return $this->hasMany(Employeemodel::class,'company_id');
    }

    //公司的门店
    public function store(){

        return $this->hasMany(Storemodel::class,'company_id');
    }
}
