<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use EasyWeChat\Foundation\Application;
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/31
 * Time:        11:18
 * Describe:    优惠活动
 */
/**************************************************************/
/*         处理各种优惠活动的控制器, 目前还有许多要修改的地方         */
/**************************************************************/
class Activity extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('activitymodel');
    }

    /**
     * 活动列表
     */
    public function listActivity()
    {
        $filed = ['id','name','start_time','end_time','qrcode_url'];
        $activity = Activitymodel::orderBy('created_at', 'DESC')->get($filed);
        $this->api_res(0,$activity);
    }

    /**
     * 新增活动
     */
    public function addActivity()
    {
        $post = $this->input->post(null,true);

    }

    public function validation()
    {
        $this->load->library('form_validation');
        $config = array(
            array(
                'field' => 'type',
                'label' => '活动类型',
                'rules' => 'trim|required|in_list[ATTRACT,NORMAL,DISCOUNT]',
            ),
            array(
                'field' => 'name',
                'label' => '活动名称',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'start_time',
                'label' => '开始时间',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'end_time',
                'label' => '结束时间',
                'rules' => 'trim|required',
            ),
            array(
                'field' => '',
                'label' => '开始时间',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'start_time',
                'label' => '开始时间',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'start_time',
                'label' => '开始时间',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'start_time',
                'label' => '开始时间',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'start_time',
                'label' => '开始时间',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'start_time',
                'label' => '开始时间',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'start_time',
                'label' => '开始时间',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'start_time',
                'label' => '开始时间',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'start_time',
                'label' => '开始时间',
                'rules' => 'trim|required',
            ),

        );

        $this->form_validation->set_rules($config)->set_error_delimiters('','');
        return $this->form_validation->run();
    }
}
