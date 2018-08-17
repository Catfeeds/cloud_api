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
class Confirm_payment extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('ordermodel');
    }

    public function confirm()
    {
        $this->load->model('storepaymodel');
        $this->load->model('ordermodel');
        $dt =Carbon::now()->subDays(30);
        $order = Storepaymodel::where(function($query){
            $query->orwhereHas('order',function($query){
                $query->where('pay_type', 'JSAPI')->where('status', 'CONFIRM');
            });
        })->where('created_at','>=',$dt)->get()->toArray();
        if (!$order) {
            log_message('debug', '没有查询到付完款未确认的账单');
            $this->api_res(1007);
            return false;
        }
        $this->load->help('common');
        $app = new Application(getWechatCustomerConfig());

        foreach($order as $value) {
            $result = $app->payment->query($value['out_trade_no']);
            if($result->items['result_code'] == 'SUCCESS'){
                $update_statsu = Ordermodel::where('id',$value['data']['orders'][0]['id'])->update(['status'=>'COMPLATE']);
                if(!$update_statsu){
                    log_message('error','修改订单状态出错');
                }
                $this->api_res(0);
            }
            log_message('error','微信订单确认出错，错误为：'.$result->items['return_code']);
        }
    }
}
