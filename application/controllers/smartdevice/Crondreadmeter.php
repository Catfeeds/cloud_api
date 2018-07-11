<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/libraries/Readmeter.php';
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/6/10
 * Time:        18:33
 * Describe:    计划任务读表
 */
class Crondreadmeter extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('storemodel');
    }

    public function readMeter()
    {
        Storemodel::get(['id'])->each(function ($store) {
            (new Readmeter($store->id))->handle();
        });
    }
}