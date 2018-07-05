<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Author:      weijinlong
 * Date:        2018/7/5
 * Time:        10:55
 * Describe:    梵响系统线上运维类
 */

class Ping extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('companymodel');
    }

    public function index(){
        $count = Companymodel::count();
        echo $count;
    }
}