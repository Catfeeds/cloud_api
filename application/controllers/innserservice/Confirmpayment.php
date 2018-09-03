<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use EasyWeChat\Foundation\Application;
use Illuminate\Database\Capsule\Manager as DB;
use Carbon\Carbon;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/17
 * Time: 15:28
 */

class ConfirmPayment extends MY_Controller {
    public function __construct() {
        parent::__construct();
        // $this->load->model('ordermodel');
    }

    public function confirm() {
        $this->load->model('storepaymodel');
        $this->load->model('ordermodel');
        $dt    = Carbon::now()->subDays(30);
        $store_psys = Storepaymodel::where('notify_date', '>=', $dt)->where('status', Storepaymodel::STATE_UDONE)->whereHas('order',function($query){
            $query->where('status', Ordermodel::STATE_PENDING)->where('pay_type', Ordermodel::PAYWAY_JSAPI);
        })->get()->toArray();
        if (!$store_psys) {
            log_message('debug', '没有查询到付完款未确认的账单');
            $this->api_res(0);
            return false;
        }

        foreach ($store_psys as $value) {
            $out_trade_no = $value['out_trade_no'];
            if (empty($out_trade_no)) {
                continue;
            }

            $app          = new Application($this->getCustomerWechatConfig($value['store_id']));
            /*log_message("debug", "try to check wechat jsapi payment $out_trade_no");*/
            $result = $app->payment->query($out_trade_no);
            if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS' && $result->trade_state == 'SUCCESS') {
               $this->ordersConfirm($value);
            } else {
                log_message('info', "微信订单 $out_trade_no 确认出错，错误为：" . json_encode($result));
            }
        }
        $this->api_res(0);
    }

    private function ordersConfirm($value)
    {
        try {
            DB::beginTransaction();

            $this->load->model('residentmodel');
            $this->load->model('ordermodel');
            $resident = Residentmodel::with('orders')->find($value['resident_id']);

            log_message('debug', 'notify-arrived--->' . $value['out_trade_no']);

            if (empty($resident)) {
                return true;
            }
            $orders = $resident->orders()->where('status', Ordermodel::STATE_PENDING)->where('out_trade_no', $value['out_trade_no'])->get();
            if (!count($orders)) {
                return true;
            }
            $pay_date = date('Y-m-d H:i:s', time());

            foreach ($orders as $order) {
                $orderIds[] = $order->id;
                $order->pay_date = $pay_date;
                $order->status = Ordermodel::STATE_CONFIRM;
                $order->out_trade_no = $value['out_trade_no'];
                //$order->out_trade_no = $notify->out_trade_no;
                $order->save();

                if ($order->type == 'DEIVCE') {
                    $this->load->model('devicemodel');
                    $temp = Devicemodel::find($order->other_id);
                    if (!empty($temp)) {
                        $temp->status = Devicemodel::STATE_CONFIRM;
                        $temp->save();
                    }
                }

                if ($order->type == 'UTILITY') {
                    $this->load->model('utilitymodel');
                    $temp = Utilitymodel::find($order->other_id);
                    if (!empty($temp)) {
                        $temp->status = Utilitymodel::STATE_CONFIRM;
                        $temp->save();
                    }
                }
            }

            $this->load->model('couponmodel');
            Couponmodel::whereIn('order_id', $orderIds)->update(['status' => Couponmodel::STATUS_USED]);

            $this->load->model('storepaymodel');
            $store_pay = Storepaymodel::where('resident_id', $resident->id)->where('out_trade_no', $value['out_trade_no'])->first();

            if (!empty($store_pay)) {
                $store_pay->notify_date = $pay_date;
                $store_pay->status = 'DONE';
                $store_pay->save();
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            log_message('error', $e->getMessage());
            throw $e;
        }
        return true;
    }
}
