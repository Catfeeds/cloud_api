<?php
/**
 * Author:      chenkk<cooook@163.com>
 * Date:        2018/5/29
 * Time:        16:25
 * Describe:    员工与门店映射
 */

class Employeestoremapmodel extends Basemodel
{
    public function __construct()
    {
        parent::__construct();
    }

    public $timestamps=false;
    
    protected $table    = 'employee_store';

    protected $hidden   = ['created_at', 'updated_at', 'deleted_at'];
}