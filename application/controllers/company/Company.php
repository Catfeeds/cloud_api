<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/8/15
 * Time:        10:41
 * Describe:    公司
 */
class Company extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function Register()
    {
        $post   = $this->input->post(null,true);

    }
    
    public function test()
    {
    	$this->load->model('employeemodel');
    	var_dump(Employeemodel::find(163)->toArray());
    }
}