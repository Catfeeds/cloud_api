<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/9 0009
 * Time:        20:42
 * Describe:
 */

class Checkout extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('checkoutmodel');
        $this->load->model('storemodel');
        $this->load->model('residentmodel');
        $this->load->model('employeemodel');
        $this->load->model('Roomunionmodel');
        $this->load->model('Ordermodel');
    }

    //退房账单列表
    function list() {

        $input                                          = $this->input->post(null, true);
        $page                                           = isset($input['page']) ? $input['page'] : 1;
        $offset                                         = ($page - 1) * PAGINATE;
        $where                                          = [];
        empty($input['store_id']) ?: $where['store_id'] = $input['store_id'];
        empty($input['type']) ?: $where['type']         = $input['type'];
        $store_ids                                      = explode(',', $this->employee->store_ids);
        $query                                          = Checkoutmodel::with('roomunion', 'store', 'resident')
            ->where($where)->whereIn('store_id', $store_ids);

        if (!empty($input['search'])) {
            $room_ids = Roomunionmodel::where('number', $input['search'])->get()->map(function ($q) {
                return $q->id;
            });
            $query = $query->whereIn('room_id', $room_ids);
        }

        $total_page = ceil(($query->whereIn('store_id', $store_ids)->count()) / PAGINATE);

        $list = $query->orderBy('created_at', 'DESC')
            ->offset($offset)
            ->limit(PAGINATE)
            ->get();

        $this->api_res(0, ['checkouts' => $list, 'total_page' => $total_page]);
    }

    //显示一笔退款交易
    public function show() {
        $input                    = $this->input->post(null, true);
        empty($input['id']) ? $id = '' : $id = $input['id'];
        if (empty($id)) {
            $this->api_res(1007);
            return;
        }
        $checkout = Checkoutmodel::find($id);
        if (empty($checkout)) {
            $this->api_res(1007);
            return;
        }
        $data['checkout']                  = $checkout->toArray();
        $data['checkout']['bank_card_img'] = $this->fullAliossUrl($data['checkout']['bank_card_img']);
        $data['resident']                  = Residentmodel::find($checkout->resident_id)->toArray();
        $data['room']                      = Roomunionmodel::find($checkout->room_id)->toArray();
        $orders                            = Ordermodel::where('resident_id', $checkout->resident_id)->whereNotIn('status',[Ordermodel::STATE_COMPLETED, Ordermodel::STATE_CLOSE])->get();
        $data['orders']                    = $orders->toArray();
        $data['countmoney']                = number_format($orders->sum('money'), 2, '.', '');
        $data['paymoney']                  = $data['resident']['tmp_deposit'] + $data['resident']['deposit_money'] - $data['countmoney'];

        $this->api_res(0, $data);

    }

    //退房账单更新
    public function update() {

    }

    //确定正常退房
    public function sure() {
        $input                    = $this->input->post(null, true);
        empty($input['id']) ? $id = '' : $id = $input['id'];
        if (empty($id)) {
            $this->api_res(1007);
            return;
        }
        empty($input['sequence']) ? $sequence = '' : $sequence = $input['sequence'];
        if (empty($sequence)) {
            $this->api_res(1007);
            return;
        }
        empty($input['remark']) ? $remark = '' : $remark = $input['remark'];

        $input['sequence'] = '';

        //生成退款账单

        $checkout = Checkoutmodel::find($id);
        if ($checkout->status == 'COMPLETED') {
            $this->api_res(1007);
            return;
        }
        $resident   = Residentmodel::find($checkout->resident_id);
        $orders     = Ordermodel::where('resident_id', $checkout->resident_id)->where('sequence_number', '')->get();
        $new_orders = $orders->toArray();
        if (!empty($new_orders)) {
            $countmoney = $orders->sum('money');
            //将押金抵扣的金额转出
            $this->backBill($resident, $countmoney);
            //将押金抵扣的账单转为已收款
            if ($countmoney != 0) {
                $this->createBill($orders);
            }
        } else {
            $countmoney = 0;
        }
        $paymoney = $resident->tmp_deposit + $resident->deposit_money - $countmoney;

        //将剩余的金额处理掉
        $this->backBill($resident, $paymoney);

        //更新退房单
        $updatedata['refund']            = $paymoney;
        $updatedata['bank_sequence']     = $sequence;
        $updatedata['status']            = 'COMPLETED';
        $updatedata['accountant_remark'] = $remark;

        Checkoutmodel::where('id', $id)->update($updatedata);

        $data['message'] = '办理成功!';
        $this->api_res(0, $data);

    }

    //押金转收入退房

    public function Breach() {

        $input                    = $this->input->post(null, true);
        empty($input['id']) ? $id = '' : $id = $input['id'];
        if (empty($id)) {
            $this->api_res(1007);
            return;
        }
        empty($input['sequence']) ? $sequence = '' : $sequence = $input['sequence'];
        if (empty($sequence)) {
            $this->api_res(1007);
            return;
        }
        empty($input['remark']) ? $remark = '' : $remark = $input['remark'];

        $input['sequence'] = '';

        //生成退款账单

        $checkout = Checkoutmodel::find($id);
        if ($checkout->status == 'COMPLETED') {
            $this->api_res(1007);
            return;
        }
        $resident   = Residentmodel::find($checkout->resident_id);
        $orders     = Ordermodel::where('resident_id', $checkout->resident_id)->where('sequence_number', '')->get();
        $new_orders = $orders->toArray();
        if (!empty($new_orders)) {
            $countmoney = $orders->sum('money');
            //将押金抵扣的金额转出
            $this->backBill($resident, $countmoney);
            //将押金抵扣的账单转为已收款
            if ($countmoney != 0) {
                $this->createBill($orders);
            }
        } else {
            $countmoney = 0;
        }
        $paymoney = $resident->tmp_deposit + $resident->deposit_money - $countmoney;

        //将剩余的金额处理掉
        $this->backBill($resident, $paymoney, false);

        //更新退房单
        $updatedata['refund']            = $paymoney;
        $updatedata['bank_sequence']     = $sequence;
        $updatedata['status']            = 'COMPLETED';
        $updatedata['accountant_remark'] = $remark;

        Checkoutmodel::where('id', $id)->update($updatedata);

        $data['message'] = '办理成功!';
        $this->api_res(0, $data);
    }

    /**
     *
     * 创建生成流水账单
     * 根据流水账单来记录用户的每次支付记录
     *
     */

    private function createBill($orders) {
        $this->load->model('billmodel');
        $bill       = new Billmodel();
        $bill->id   = '';
        $count      = $this->billmodel->ordersConfirmedToday() + 1;
        $dateString = date('Ymd');
        $this->load->model('residentmodel');

        $bill->sequence_number = sprintf("%s%06d", $dateString, $count);

        $bill->store_id = $orders[0]->store_id;
//        $bill->employee_id         =    $orders[0]->employee_id;
        $bill->employee_id = $this->employee->id;
        $bill->resident_id = $orders[0]->resident_id;
        $bill->customer_id = $orders[0]->customer_id;
        $bill->uxid        = $orders[0]->uxid;
        $bill->room_id     = $orders[0]->room_id;
        $orderIds          = array();

        foreach ($orders as $order) {
            $orderIds[]  = $order->id;
            $bill->money = $bill->money + $order->paid;

        }

        $bill->type = 'INPUT';

//        $bill->pay_type            =    $orders[0]->pay_type;
        $bill->pay_type     = Ordermodel::PAYWAY_DEPOSIT;
        $bill->confirm      = '';
        $bill->pay_date     = date('Y-m-d H:i:s', time());
        $bill->data         = '';
        $bill->confirm_date = date('Y-m-d H:i:s', time());

        //如果是微信支付
        $bill->out_trade_no = '';
        $bill->store_pay_id = '';

        $res = $bill->save();
        if (isset($res)) {
            Ordermodel::whereIn('id', $orderIds)->update(['sequence_number' => $bill->sequence_number,
                'status'                                                        => Ordermodel::STATE_COMPLETED, 'deal' => Ordermodel::DEAL_DONE, 'pay_date' => date('Y-m-d H:i:s', time()),
                'pay_type'                                                      => Ordermodel::PAYWAY_DEPOSIT,
            ]);
        }
        return $res;
    }

    /**
     *
     * 退款流水账单
     * 根据流水账单来记录用户的每次支付记录
     *
     */

    private function backBill($resident, $backmoney, $isback = true) {
        $this->load->model('billmodel');
        $bill                  = new Billmodel();
        $bill->id              = '';
        $count                 = $this->billmodel->ordersConfirmedToday() + 1;
        $dateString            = date('Ymd');
        $bill->sequence_number = sprintf("%s%06d", $dateString, $count);

        $bill->store_id    = $resident->store_id;
        $bill->employee_id = $this->employee->id;
        $bill->resident_id = $resident->id;
        $bill->customer_id = $resident->customer_id;
        $bill->uxid        = $resident->uxid;
        $bill->room_id     = $resident->room_id;
        if ($isback) {
            $bill->type = 'OUTPUT';
        } else {
            $bill->type = 'INPUT';
        }
        $bill->money        = $backmoney;
        $bill->pay_type     = 'DEPOSIT';
        $bill->confirm      = '';
        $bill->pay_date     = date('Y-m-d H:i:s', time());
        $bill->data         = '';
        $bill->confirm_date = date('Y-m-d H:i:s', time());

        //如果是微信支付
        $bill->out_trade_no = '';
        $bill->store_pay_id = '';

        $res = $bill->save();
        return $res;

    }

    private function allStatus() {

        return array(
            Checkoutmodel::STATUS_APPLIED,
            Checkoutmodel::STATUS_UNPAID,
            Checkoutmodel::STATUS_PENDING,
            Checkoutmodel::STATUS_BY_MANAGER,
            Checkoutmodel::STATUS_MANAGER_APPROVED,
            Checkoutmodel::STATUS_PRINCIPAL_APPROVED,
            Checkoutmodel::STATUS_COMPLETED,
        );
    }

}
