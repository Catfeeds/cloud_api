<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/21
 * Time:        17:44
 * Describe:    来访登记
 */
class Visitrecord extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('Reserveordermodel');
    }

    /*
     * 来访登记信息
     */
    public function visit() {
        $post = $this->input->post(null, true);
        if (!$this->validation()) {
            $fieldarr = ['visit_by', 'name', 'phone', 'time', 'work_address', 'info_source', 'room_type_id',
                'people_count', 'check_in_time', 'guest_type', 'require', 'remark'];
            $this->api_res(1002, ['errmsg' => $this->form_first_error($fieldarr)]);
            return;
        }
        $reserve = new Reserveordermodel();
        $reserve->fill($post);
        $reserve->employee_id = $this->current_id;
        $reserve->store_id    = $this->employee->store_id;
        if ($reserve->save()) {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }
    }

    /**
     * 表单验证
     */
    public function validation() {
        $this->load->library('form_validation');
        $config = array(
            array(
                'field' => 'visit_by',
                'label' => '来访类型',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'name',
                'label' => '姓名',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'phone',
                'label' => '联系电话',
                'rules' => 'trim|required|max_length[13]',
            ),
            array(
                'field' => 'time',
                'label' => '来访时间',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'work_address',
                'label' => '工作地点',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'info_source',
                'label' => '信息来源',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'people_count',
                'label' => '入住人数',
                'rules' => 'trim|required|integer',
            ),
            array(
                'field' => 'room_type_id',
                'label' => '房型',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'check_in_time',
                'label' => '入住时间',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'guest_type',
                'label' => '顾客类型',
                'rules' => 'trim|required|in_list[A,B,C,D]',
            ),
            array(
                'field' => 'require',
                'label' => '需求',
                'rules' => 'trim',
            ),
            array(
                'field' => 'remark',
                'label' => '备注',
                'rules' => 'trim',
            ),
        );

        $this->form_validation->set_rules($config)->set_error_delimiters('', '');
        return $this->form_validation->run();
    }
}
