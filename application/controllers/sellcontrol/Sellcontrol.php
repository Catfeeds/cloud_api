<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/25
 * Time:        18:37
 * Describe:    销控管理
 */

class Sellcontrol extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('roomunionmodel');
    }

    public function details()
    {

    }
}