<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;
use EasyWeChat\Foundation\Application;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/17
 * Time: 15:28
 */

class ConfirmPayment extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('ordermodel');
    }

    public function confirm() {
        $this->load->model('storepaymodel');
        $this->load->model('ordermodel');
        $dt    = Carbon::now()->subDays(30);
        $order = Storepaymodel::where(function ($query) {
            $query->orwhereHas('order', function ($query) {
                $query->where('pay_type', 'JSAPI')->where('status', 'CONFIRM');
            });
        })->where('created_at', '>=', $dt)->get()->toArray();
        if (!$order) {
            log_message('debug', '没有查询到付完款未确认的账单');
            $this->api_res(0);
            return false;
        }
        $this->load->helper('common');
        $app = new Application(getWechatCustomerConfig());

        log_message("debug", "try to check " . count($order) . " payments");
        foreach ($order as $value) {
            $out_trade_no = $value['out_trade_no'];
            if (empty($out_trade_no)) {
                continue;
            }

            log_message("debug", "try to check wechat jsapi payment $out_trade_no");
            $result = $app->payment->query($out_trade_no);
            if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS') {
                $update_statsu = Ordermodel::where('id', $value['data']['orders'][0]['id'])->update(['status' => 'COMPLATE']);
                if (!$update_statsu) {
                    log_message('error', '修改微信订单状态出错');
                } else {
                    log_message('info', "微信订单 $out_trade_no 状态成功.");
                }
            } else {
                log_message('info', "微信订单 $out_trade_no 确认出错，错误为：" . json_encode($result));
            }
        }
    }
}
