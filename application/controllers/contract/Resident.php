<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * User: wws
 * Date: 2018-05-24
 * Time: 09:23
 *   运营住户合同
 */

class Resident extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('contractmodel');
    }

    /**
     * 住户管理合同信息
     */
    public function resident()
    {

    }

}