
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

    protected $table    = 'employees';

    protected $hidden  = [];

    //员工办理的客户
    public function resident(){

        return $this->hasMany(Residentmodel::class,'employee_id');
    }
}
