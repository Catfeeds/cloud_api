<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      chenkk<cooook@163.com>
 * Date:        2018/5/24
 * Time:        11:39
 * Describe:    住户
 */

class Residentct extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('residentmodel');
    }

    /**
     * 显示住户中心
     */
    public function showCenter()
    {
        $post = $this->input->post(null, true);
        if (isset($post['id']) && !empty($post['id'])) {
            $id = $post['id'];
            $resident = Residentmodel::find($id);
            $category = [$resident->name, $resident->status];
            $this->api_res(0, $category);
        } else {
            $this->api_res(1002);
            return;
        }
    }

    /**
     * 显示住户详情
     */
    public function showDetail()
    {

    }

}