<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/22
 * Time:        9:33
 * Describe:    个人中心
 */
class Personalcenter extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('employeemodel');
    }

    /**
     * 个人中心主页
     */
    public function center()
    {

    }

}