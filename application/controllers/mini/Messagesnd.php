<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use EasyWeChat\Foundation\Application;
/**
 * Author:      chenkk<cooook@163.com>
 * Date:        2018/5/25
 * Time:        20:27
 * Describe:    推送通知
 */

class Messagesnd extends MY_Controller
{
    protected $template_message;

    public function __construct()
    {
        parent::__construct();
    }

    public function sendMsgType()
    {
        return  ['type' =>
                        [ 0 => '停水通知',
                          1 => '停电通知',
                          2 => '活动通知',
                          3 => '设施更新通知'
                        ]
                ];
    }

    public function templateFields()
    {
        $post = $this->input->post(null, true);
        $type = isset($post['type']) ? $post['type'] : null;
        $title = $this->getNoticeType($type);
        if (!$title) $this->api_res(0, ['error' => '未找到通知标题']);
        $this->api_res(0, $title);
    }

    public function sendNotice()
    {
        $post = $this->input->post(null, true);
        //$config = $this->validation();
        /*if(!$this->validationText($config))
        {
            $fieldarr   = ['store_id', 'type', 'title', 'hremind', 'time', 'area', 'reason', 'fremind', 'preview'];
            $this->api_res(1002,['error'=>$this->form_first_error($fieldarr)]);
            return false;
        }*/

        $store_id = $post['store_id'];
        $this->load->model('residentmodel');
        $this->load->model('customermodel');
        $customers = Residentmodel::with(['customer' => function ($query) {
            $query->select('id','openid');
        }])->where('store_id', $store_id)->get(['customer_id']);
        //$this->api_res(0,$customers);
        $type = $post['type'];
        //$title = $this->getNoticeType($type);
        $template_id = $this->getTemplateIds($type);

        $this->load->helper('common');
        $app = new Application(getMiniWechatConfig());
        foreach ($customers as $customer) {
            $app->notice->send([
                'touser' => $customer->openid,
                'template_id' => $template_id,
                'url' => 'https://easywechat.org',
                'data' => [
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

    public function getTemplateIds($type)
    {
        switch ($type) {
            case '0':
                return 'UXFAM4yAgFQ--rwIqIkpmltfz6n3nIQW7COgIwm32v8';
            case '1':
                return 'OhCKlytLt8bUCiP9xhNFNtq1NmV_KbLBBuyS7EJGnSa';
            case '2':
                break;
            case '3':
                break;
            default:
                break;
        }
    }

    public function getNoticeType($type)
    {
        switch ($type) {
            case '0':
                return [
                    'title'   => '停水通知',
                    'hremind' => '首段提醒',
                    'time'    => '停水时间',
                    'area'    => '停水区域',
                    'reason'  => '停水原因',
                    'fremind' => '末尾提醒',
                    'preview' => '预览'
                ];
            case '1':
                return [
                    'title'   => '停电通知',
                    'hremind' => '首段提醒',
                    'time'    => '停电时间',
                    'area'    => '停电区域',
                    'reason'  => '停电原因',
                    'fremind' => '末尾提醒',
                    'preview' => '预览'
                ];
            case '2':
                return [
                    'title'   => '停电通知',
                    'hremind' => '首段提醒',
                    'time'    => '累计天数',
                    'area'    => '累计金额',
                    'reason'  => '停电原因',
                    'fremind' => '末尾提醒',
                    'preview' => '预览'
                ];
            case '3':
                break;
            default:
                break;
        }
    }

    /**
     * 验证
     */
    public function validation()
    {
        $config = array(
            array(
                'id' => 'store_id',
                'label' => '门店id',
                'rules' => 'trim|required',
            ),
            array(
                'type' => 'type',
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
            array(
                'field' => 'preview',
                'label' => '预览',
                'rules' => 'trim|required',
            ),
        );
        return $config;
    }


}