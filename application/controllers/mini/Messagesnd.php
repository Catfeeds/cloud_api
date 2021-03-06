<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use EasyWeChat\Foundation\Application;

/**
 * Author:      chenkk<cooook@163.com>
 * Date:        2018/5/25
 * Time:        20:27
 * Describe:    推送通知
 */

class Messagesnd extends MY_Controller {
    protected $template_message;

    public function __construct() {
        parent::__construct();
    }

    public function sendMsgType() {
        $field = [
            [
                'field' => 'TS',
                'name'  => '停水通知',
            ],
            [
                'field' => 'TD',
                'name'  => '停电通知',
            ],
            [
                'field' => 'HD',
                'name'  => '活动通知',
            ],
            [
                'field' => 'SSGX',
                'name'  => '设施更新通知',
            ],
        ];
        $this->api_res(0, ['type' => $field]);
    }

    public function templateFields() {
        $post = $this->input->post(null, true);
        $type = isset($post['type']) ? $post['type'] : null;
        if (!$type) {
            $this->api_res(1002, ['type' => '请输入通知类型']);
            return;
        }
        $title = $this->getNoticeType($type);
        if (!$title) {
            $this->api_res(0, ['error' => '未找到通知标题']);
        }

        $this->api_res(0, ['data' => $title]);
    }

    public function sendNotice() {
        $post   = $this->input->post(null, true);
        $config = $this->validation();
        if (!$this->validationText($config)) {
            $fieldarr = ['store_id', 'type', 'title', 'hremind', 'time', 'area', 'reason', 'fremind', 'preview'];
            $this->api_res(1002, ['error' => $this->form_first_error($fieldarr)]);
            return false;
        }

        $store_id = $post['store_id'];
        $this->load->model('residentmodel');
        $this->load->model('customermodel');
        $customers = Residentmodel::with(['customer' => function ($query) {
            $query->select('id', 'openid');
        }])->whereIn('store_id', $store_id)->where('status', 'NORMAL')->get(['customer_id']);
        //$this->api_res(0,$customers);
        $type  = $post['type'];
        $title = $this->getNoticeType($type);
        if (!$title) {
            $this->api_res(1007, ['error' => '没有找到通知标题']);
            return;
        }
        $template_id = $this->getTemplateIds($type);
        if (!$template_id) {
            $this->api_res(1007, ['error' => '没有找到通知模版']);
            return;
        }

        $this->load->helper('common');
        $app = new Application(getMiniWechatConfig());
        foreach ($customers as $customer) {
            $app->notice->send([
                'touser'      => $customer->openid,
                'template_id' => $template_id,
                'url'         => 'https://easywechat.org',
                'data'        => [
                    'keyword1' => $post['hremind'],
                    'keyword2' => $post['time'],
                    'keyword3' => $post['area'],
                    'keyword4' => $post['reason'],
                    'keyword5' => $post['fremind'],
                    'keyword6' => $post['preview'],
                ],
            ]);
        }

    }

    public function getTemplateIds($type) {
        switch ($type) {
        case 'TS':
            return 'UXFAM4yAgFQ--rwIqIkpmltfz6n3nIQW7COgIwm32v8';
        case 'TD':
            return 'UXFAM4yAgFQ--rwIqIkpmltfz6n3nIQW7COgIwm32v8';
        case 'HD':
            return 'UXFAM4yAgFQ--rwIqIkpmltfz6n3nIQW7COgIwm32v8';
        case 'SSGX':
            return 'UXFAM4yAgFQ--rwIqIkpmltfz6n3nIQW7COgIwm32v8';
        default:
            return null;
        }
    }

    public function getNoticeType($type) {
        switch ($type) {
        case 'TS':
            return
            array(
                array(
                    'field' => 'title',
                    'name'  => '停水通知',
                ),
                array(
                    'field' => 'hremind',
                    'name'  => '首段提醒',
                ),
                array(
                    'field' => 'time',
                    'name'  => '停水时间',
                ),
                array(
                    'field' => 'area',
                    'name'  => '停水区域',
                ),
                array(
                    'field' => 'reason',
                    'name'  => '停水原因',
                ),
                array(
                    'field' => 'fremind',
                    'name'  => '末尾提醒',
                ),
            );

        case 'TD':
            return
            array(
                array(
                    'field' => 'title',
                    'name'  => '停电通知',
                ),
                array(
                    'field' => 'hremind',
                    'name'  => '首段提醒',
                ),
                array(
                    'field' => 'time',
                    'name'  => '停电时间',
                ),
                array(
                    'field' => 'area',
                    'name'  => '停电区域',
                ),
                array(
                    'field' => 'reason',
                    'name'  => '停电原因',
                ),
                array(
                    'field' => 'fremind',
                    'name'  => '末尾提醒',
                ),
            );
        case 'HD':
            return
            array(
                array(
                    'field' => 'title',
                    'name'  => '标题',
                ),
                array(
                    'field' => 'hremind',
                    'name'  => '首段提醒',
                ),
                array(
                    'field' => 'time',
                    'name'  => '活动时间',
                ),
                array(
                    'field' => 'area',
                    'name'  => '活动区域',
                ),
                array(
                    'field' => 'fremind',
                    'name'  => '末尾提醒',
                ),
            );
        case 'SSGX':
            return
            array(
                array(
                    'field' => 'title',
                    'name'  => '标题',
                ),
                array(
                    'field' => 'hremind',
                    'name'  => '首段提醒',
                ),
                array(
                    'field' => 'time',
                    'name'  => '设施更新时间',
                ),
                array(
                    'field' => 'area',
                    'name'  => '设施更新区域',
                ),
                array(
                    'field' => 'fremind',
                    'name'  => '末尾提醒',
                ),
            );
        default:
            return null;
        }
    }

    /**
     * 验证
     */
    public function validation() {
        $config = array(
            array(
                'id'    => 'store_id',
                'label' => '门店id',
                'rules' => 'trim|required',
            ),
            array(
                'type'  => 'type',
                'label' => '通知类型',
                'rules' => 'trim|required|in_list[0,1,2]',
            ),
            array(
                'field' => 'title',
                'label' => '通知标题',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'hremind',
                'label' => '首段提醒',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'time',
                'label' => '通知时间',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'area',
                'label' => '通知区域',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'reason',
                'label' => '通知原因',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'fremind',
                'label' => '末尾提醒',
                'rules' => 'trim|required',
            ),
//            array(
            //                'field' => 'preview',
            //                'label' => '预览',
            //                'rules' => 'trim|required',
            //            ),
        );
        return $config;
    }

}
