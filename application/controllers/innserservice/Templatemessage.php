<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 微信模板消息
 */
class Templatemessage extends MY_Controller {
    public function __construct() {
        parent::__construct();
    }

    /**
     * {{first.DATA}}
    客户姓名：{{keyword1.DATA}}
    客户手机：{{keyword2.DATA}}
    预约时间：{{keyword3.DATA}}
    预约内容：{{keyword4.DATA}}
    {{remark.DATA}}
     * form参数
     * store_id: 门店id
     * name: 预约用户姓名
     * phone: 预约用户手机
     * time: 预约时间
     * content: 预约内容
     */
    public function sendReserveMsg() {
        $this->load->model('positionmodel');
        $this->load->model('employeemodel');
    }
}
