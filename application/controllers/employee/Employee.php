<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/14 0014
 * Time:        14:46
 * Describe:    员工管理
 */
class Employee extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('employeemodel');
    }


}