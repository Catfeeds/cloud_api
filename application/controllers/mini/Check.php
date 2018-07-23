<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * User:    wws
 * Date:    2018-05-22
 * Time:    14:45
 * Describe:    退房
 */
class Check extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('reserveordermodel');
    }

    /**
     * 办理退房
     */
    public function CheckOut() {
        $this->load->model('roomtypemodel');
        $post = $this->input->post(NULL, true);

    }

    /**
     * @return
     * 表单验证
     */
    public function validation() {
        $this->load->library('form_validation');
        $config = array(
            array(
                'field' => 'work_address',
                'label' => '工作地点',
                'rules' => 'trim|required',
            ),

        );
        $this->form_validation->set_rules($config)->set_error_delimiters('', '');
        return $this->form_validation->run();
    }

}
