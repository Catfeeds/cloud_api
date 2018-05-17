<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/14 0014
 * Time:        14:46
 * Describe:    员工职位
 */
class Position extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('positionmodel');

    }

    public function editPosition()
    {
        $filed = ['name', 'pc_privilege', 'mini_privilege'];
        $category = $this->positionmodel->get($filed);
        $this->api_res(0, $category);
    }


}