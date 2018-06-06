<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      chenkk<cooook@163.com>
 * Date:        2018/6/6
 * Time:        11:21
 * Describe:    首页展示
 */
class Home extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function home()
    {
        $post = $this->input->post(null,true);
        $this->load->model('reserveordermodel');
        $this->load->model('ordermodel');
    }
}