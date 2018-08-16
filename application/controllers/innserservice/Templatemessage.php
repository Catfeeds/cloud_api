<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use EasyWeChat\Foundation\Application;
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
     * visit_time: 预约时间
     * content: 预约内容
     */
    public function sendReserveMsg() {
        $this->load->model('positionmodel');
        $this->load->model('employeemodel');
        $post=$this->input->post(null,true);
        $position = '店长';
        $employee = Employeemodel::where(function ($query) use ($position) {
            $query->orwherehas('position', function ($query) use ($position) {
                $query->where('name', $position);
            });
        })->get(['employee_mp_openid', 'store_ids', 'name'])->toArray();
        if (!$employee) {
            $this->api_res(0);
            return false;
        }
        $data = [
            'first'    => '有新的预约消息',
            'keyword1' =>  $post['name'],
            'keyword2' =>  $post['phone'],
            'keyword3' =>  $post['visit_time'],
            'keyword4' => '看房预约',
            'remake'   => '如有疑问请与工作人员联系'
        ];
        if (!empty($post['content'])){
            $data ['keyword4']= $post['content'];
        }
        $this->load->helper('common');
        $app = new Application(getWechatEmployeeConfig());
        foreach ($employee as $value) {
            $store_arr = explode(',',$value['store_ids']);
            if(in_array($post['store_id'],$store_arr)) {
                try {
                    log_message('debug', 'try to 预约发送模版消息');
                    $app->notice->uses(config_item('tmplmsg_employee_Reserve'))
                        ->withUrl(config_item('wechat_url') . '')
                        ->andData($data)
                        ->andReceiver($value->employee_mp_openid)
                        ->send();
                    log_message('info', '微信回调成功发送模板消息: ' . $value->name);
                } catch (Exception $e) {
                    log_message('error', '租户预约模板消息通知失败：' . $e->getMessage());
                    throw $e;
                }
            }
        }
    }
}
