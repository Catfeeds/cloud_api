<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/1 0001
 * Time:        17:47
 * Describe:
 */
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;

class Checkout extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('checkoutmodel');
        $this->load->model('residentmodel');
        $this->load->model('ordermodel');
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');

    }

    /**
     * 显示退房记录列表
     * 如果携带参数status, 则检索该status的记录, 若不携带参数, 则检索未完成的记录
     */
    public function listCheckout() {
        $input             = $this->input->post(null, true);
        $where['store_id'] = $this->employee->store_id;
        if (isset($input['status'])) {
            $status = [$input['status']];
        } else {
            //$status = $this->allStatus();
            $status = array_diff($this->allStatus(), [Checkoutmodel::STATUS_COMPLETED]);
        }
        $list = Checkoutmodel::with(['roomunion', 'store', 'resident'])->where($where)->whereIn('status', $status)->get();
        if (isset($input['room_number'])) {
            $list = $list->where('roomunion.number', $input['room_number']);
        }
        $this->api_res(0, ['checkouts' => $list]);
    }

    /**
     * 提交新的退房订单
     */
    public function store() {
        $field = ['room_id', 'resident_id', 'pay_or_not', 'type', 'water', 'electricity',
            'clean', 'compensation', 'other_deposit_deduction'];
        $input    = $this->input->post(null, true);
        $store_id = $this->employee->store_id;
        if (!$this->validationText($this->validateStore())) {
            $this->api_res(1002, ['error' => $this->form_first_error($field)]);
            return;
        }
        //正常退房 不能押金抵扣，如果押金抵扣了，就一定是违约退房
        if (!$this->checkCheckOutType($input)) {
            $this->api_res(10025);
            return;
        }

        //检查是否已经存在该住户的退房记录
        $record = Checkoutmodel::where(['resident_id' => $input['resident_id']])->count();
        if ($record > 0) {
            $this->api_res(10026);
            return;
        }

        $resident = Residentmodel::where('store_id', $store_id)->find($input['resident_id']);
        if (!$resident) {
            $this->api_res(1007);
            return;
        }
        if ($resident->status != Residentmodel::STATE_NORMAL) {
            $this->api_res(10011);
            return;
        }

        try {
            DB::beginTransaction();
            //创建退房记录
            $checkout                          = new Checkoutmodel();
            $checkout->resident_id             = $input['resident_id'];
            $checkout->room_id                 = $input['room_id'];
            $checkout->employee_id             = $this->employee->id;
            $checkout->pay_or_not              = $input['pay_or_not'];
            $checkout->type                    = $input['type'];
            $checkout->other_deposit_deduction = $input['other_deposit_deduction'];
            $checkout->status                  = Checkoutmodel::STATUS_UNPAID;
            $checkout->store_id                = $store_id;
            $checkout->time                    = Carbon::now();
//            $checkout->data         = ['checkout_orders'=>['water'=>3]];
            //{"water":2,"checkout_orders":{"water":3149,"clean":3150,"electricity":3151,"compensation":3152},"checkout_money":{"water":"1","clean":"2","electricity":"1","compensation":"3"}}
            $checkout->save();

            $number = $this->ordermodel->getOrderNumber();

            $bills['water']        = $input['water'];
            $bills['clean']        = $input['clean'];
            $bills['electricity']  = $input['electricity'];
            $bills['compensation'] = $input['compensation'];
            //生成退房时的订单
            $this->createOrUpdateCheckOutOrders(
                $checkout,
                $bills,
                $resident,
                $resident->roomunion,
                $number
            );

            $this->handleRentAndManagement($resident, $checkout, $number);
            $this->setRecordStatus($resident, $checkout);

            DB::commit();

            $this->api_res(0, ['checkout_id' => $checkout->id]);

        } catch (Exception $e) {

            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 提交给店长审核
     */
    public function submitForApproval() {
        $field = [
            'checkout_id', 'account', 'bank', 'bank_card_number', 'bank_card_img', 'employee_remark',
        ];

        if (!$this->validationText($this->validateSubmitForApproval())) {
            $this->api_res(1002, ['error' => $this->form_first_error($field)]);
            return;
        }
        $input    = $this->input->post(null, true);
        $store_id = $this->employee->store_id;
        $where    = ['store_id' => $store_id];
        $this->load->model('checkoutmodel');
        $this->load->model('residentmodel');
        $this->load->model('roomunionmodel');
        $this->load->model('ordermodel');
        $record = Checkoutmodel::where($where)->find($input['checkout_id']);
        if (!$record) {
            $this->api_res(1007);
            return;
        }
        //判断状态
        if (!in_array($record->status, [Checkoutmodel::STATUS_UNPAID, Checkoutmodel::STATUS_PENDING])) {
            $this->api_res(10027);
            return;
        }

        $resident = $record->resident;
        $room     = $record->roomunion;
        try {
            DB::beginTransaction();
            $this->storeRefundAccountAndRemark($record, $input);

            //处理退房时的账单, 并确定退房时的各种款项, 包括欠费等等
            if (false === $this->handleCheckOutDebt($record, $resident, $room)) {
                return;
            }

            //处理退房明细
            $this->handleCheckOutBills($record);

            //更新住户状态
            $resident->status      = $record->type;
            $resident->refund_time = $record->time;
            $resident->save();

            //重置原房间状态
            $resident->roomunion->update(
                [
                    'status'       => Roomunionmodel::STATE_BLANK,
                    'people_count' => 0,
                    'resident_id'  => 0,
                ]
            );

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
        $this->api_res(0, ['checkout_id' => $record->id]);
    }

    /**
     * 店长或者运营经理的审核
     */
    public function approve() {
        $field = ['remark', 'operator_role', 'checkout_id'];
        if (!$this->validationText($this->validateApprove())) {
            $this->api_res(1002, ['error' => $this->form_first_error($field)]);
            return;
        }
        $input  = $this->input->post(null, true);
        $role   = $input['operator_role'];
        $id     = $input['checkout_id'];
        $remark = isset($input['remark']) ? $input['remark'] : '无';

        if ('PRINCIPAL' == $role AND !$this->isPrincipal()) {
            $this->api_res(1011);
            return;
        } elseif (!$this->isManager() AND !$this->isPrincipal()) {
            $this->api_res(1011);
            return;
        }

        $this->load->model('checkoutmodel');
        $record = Checkoutmodel::find($id);
        if (!$record) {
            $this->api_res(1007);
            return;
        }

        if ('MANAGER' == $role) {
            if (Checkoutmodel::STATUS_BY_MANAGER != $record->status) {
                $this->api_res(10027);
                return;
            }
            $record->status         = Checkoutmodel::STATUS_MANAGER_APPROVED;
            $record->manager_remark = $remark;
        }

        if ('PRINCIPAL' == $role) {
            if (Checkoutmodel::STATUS_MANAGER_APPROVED != $record->status) {
                $this->api_res(10027);
                return;
            }
            $record->status           = Checkoutmodel::STATUS_PRINCIPAL_APPROVED;
            $record->principal_remark = $remark;
        }

        if ($record->save()) {
            $this->api_res(0, ['checkout_id' => $record->id]);
        } else {
            $this->api_res(1009);
        }
    }

    /**
     * 显示退房记录的详情
     * 根据记录的状态不同, 显示不同的信息
     * 未提交审核前, 调取相关表查询数据
     */
    public function show() {
        $input    = $this->input->post(null, true);
        $id       = $input['checkout_id'];
        $store_id = $this->employee->store_id;
        $this->load->model('checkoutmodel');
        $this->load->model('residentmodel');
        $this->load->model('ordermodel');
        $this->load->model('roomunionmodel');
        $record = Checkoutmodel::find($id);
        if (!$record) {
            $this->api_res(1007);
            return;
        }
        $resident = $record->resident;
        $data     = $record->data;

        if (in_array($record->status, [
            Checkoutmodel::STATUS_APPLIED,
            Checkoutmodel::STATUS_UNPAID,
            Checkoutmodel::STATUS_PENDING,
        ])) {
            $orderIds = isset($data['checkout_orders']) ? $data['checkout_orders'] : [];
            $debt     = $resident->orders()->where('status', Ordermodel::STATE_PENDING)->sum('money');
            $bills    = $resident->orders()
                ->whereIn('id', $orderIds)
                ->get()
                ->groupBy('type')
                ->map(function ($items) {
                    return $items->sum('money');
                })
                ->union($this->ordermodel->orderMoneyCheckOutInit());

            if (Checkoutmodel::TYPE_NORMAL == $record->type) {
                $depositTrans = 0;
                $deduction    = 0;
                $refund       = $resident->deposit_money + $resident->tmp_deposit - $record->other_deposit_deduction;
            } elseif ($record->pay_or_not) {
                $depositTrans = $resident->deposit_money + $resident->tmp_deposit - $record->other_deposit_deduction;
                $deduction    = 0;
                $refund       = $resident->tmp_deposit - $record->other_deposit_deduction;
            } else {
                $depositTrans = $resident->deposit_money - $debt;
                $deduction    = $debt;
                $refund       = 0;
            }

        } else {

            $bills        = isset($data['checkout_money']) ? $data['checkout_money'] : $this->ordermodel->orderMoneyCheckOutInit();
            $debt         = $record->debt;
            $refund       = $record->refund;
            $depositTrans = $record->deposit_trans;
            $deduction    = $record->rent_deposit_deduction;
        }

        $data = array(
            'room'                    => [
                'id'     => $resident->roomunion->id,
                'number' => $resident->roomunion->number,
            ],
            'resident'                => [
                'name'                => $resident->name,
                'card_one_url'        => $this->fullAliossUrl($resident->card_one),
                'card_two_url'        => $this->fullAliossUrl($resident->card_two),
                'card_three_url'      => $this->fullAliossUrl($resident->card_three),
                'begin_time'          => $resident->begin_time->format('Y-m-d'),
                'end_time'            => $resident->end_time->format('Y-m-d'),
                'deposit_money_rent'  => $resident->deposit_money,
                'deposit_money_other' => $resident->tmp_deposit,
                'rent_type'           => $resident->rent_type,
                'rent_price'          => $resident->real_rent_money,
                'management_price'    => $resident->real_property_costs,
                'phone'               => $resident->phone,
                'card_number'         => $resident->card_number,
            ],
            'time'                    => $record->time->format('Y-m-d'),
            'type'                    => $record->type,
            'pay_or_not'              => $record->pay_or_not,
            'debt'                    => $debt,
            'rent_deposit_deduction'  => (int) $deduction,
            'other_deposit_deduction' => (int) $record->other_deposit_deduction,
            'refund'                  => $refund,
            'bills'                   => $bills,
            'deposit_trans'           => $depositTrans,
            'account'                 => $record->account,
            'bank'                    => $record->bank,
            'bank_card_number'        => $record->bank_card_number,
            'employee_remark'         => $record->employee_remark,
            'manager_remark'          => $record->manager_remark,
            'principal_remark'        => $record->principal_remark,
            'accountant_remark'       => $record->accountant_remark,
            'status'                  => $record->status,
        );

        $this->api_res(0, ['data' => $data]);
    }

    /**
     * 取消办理退房
     */
    public function destroy() {
        $input = $this->input->post(null, true);
        $id    = $input['checkout_id'];
        $this->load->model('checkoutmodel');

        $record = Checkoutmodel::find($id);
        if (!$record) {
            $this->api_res(1007);
            return;
        }
        if (!in_array($record->status, [Checkoutmodel::STATUS_UNPAID, Checkoutmodel::STATUS_APPLIED])) {
            $this->api_res(10027);
            return;
        }

        $this->load->model('ordermodel');

        //删除退房生成的订单
        try {
            DB::beginTransaction();
            $data = $record->data;
            if (isset($data['checkout_orders'])) {

                $ids = $data['checkout_orders'];

                $query = Ordermodel::whereIn('id', $ids)->whereIn('status', [Ordermodel::STATE_CONFIRM, Ordermodel::STATE_COMPLETED]);

                if ($query->exists()) {

                    $this->api_res(10030);
                    return;

                }

                Ordermodel::whereIn('id', $ids)->delete();
            }
            $record->delete();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
        $this->api_res(0);
    }

    /**
     * 处理退房时的明细
     */
    private function handleCheckOutBills($record) {
        $data = $record->data;

        if (empty($data['checkout_orders'])) {
            return true;
        }

        $orders = Ordermodel::whereIn('id', $data['checkout_orders'])->get()->toArray();

        if (0 == count($orders)) {
            return true;
        }

        $bills = $this->ordermodel->orderMoneyCheckOutInit();

        foreach ($bills as $type => $money) {
            if ($order = $orders->where('type', $type)->first()) {
                $bills[$type] = $order->paid;
            } else {
                $bills[$type] = 0;
            }
        }
        $data['checkout_money'] = $bills;

        $record->data = $data;
        $record->save();

        return $record;
    }

    /**
     * 记录提交审核的时候提交的数据
     */
    private function storeRefundAccountAndRemark($record, $input) {
        foreach (['bank', 'account', 'bank_card_number', 'employee_remark'] as $key) {

            if (isset($input[$key])) {
                $data[$key] = $input[$key];
            }
        }

        if (isset($input['bank_card_img'])) {
            $record->bank_card_img = $this->splitAliossUrl($input['bank_card_img']);
        }

        if (isset($data)) {
            $record->fill($data);

            $record->save();
        }

        return $record;
    }

    /**
     * 店员提交审核时退款金额, 抵扣费用的计算
     */
    private function handleCheckOutDebt($record, $resident, $room) {
        //查询出所有的未缴费订单
        $orders = $resident->orders()->where('status', Ordermodel::STATE_PENDING)->get();
        $debt   = $orders->sum('money');

        if ($debt > 0) {
            if ($record->pay_or_not OR Checkoutmodel::TYPE_NORMAL == $record->type) {
                $this->api_res(10028);
                return false;
            } elseif ($resident->deposit_money < $debt) {
                $this->api_res(10029);
                return false;
            }
        }

        //需要添加一笔订单, 记录其他押金的抵扣
        if (0 < $record->other_deposit_deduction) {

            $order = $this->ordermodel->addCheckOutOrderByType(
                $resident,
                $room,
                count($orders) ? $orders->first()->number : $this->ordermodel->getOrderNumber(),
                $this->employee->id,
                Ordermodel::PAYTYPE_OTHER,
                $record->other_deposit_deduction,
                $record->time
            );

            $orders->push($order);
        }

        if (count($orders)) {
            $this->ordermodel->payByDeposit($orders->pluck('id')->toArray());
        }

        //根据是否是违约退房计算退还金额
        if (Checkoutmodel::TYPE_NORMAL == $record->type) {
            $refund       = $resident->deposit_money + $resident->tmp_deposit - $record->other_deposit_deduction;
            $depositTrans = 0;
        } else {
            $refund       = $resident->tmp_deposit - $record->other_deposit_deduction;
            $depositTrans = $resident->deposit_money - $debt;
        }

        $record->debt                   = $debt;
        $record->deposit_trans          = $depositTrans;
        $record->refund                 = $refund;
        $record->rent_deposit_deduction = $debt;
        $record->status                 = Checkoutmodel::STATUS_BY_MANAGER;
        $record->save();

        return $record;
    }

    /**
     * 创建或更新退房时的账单, 包括水费, 电费, 垃圾清理费, 物品赔偿费以及需补交的房租和物业费
     * 有则更新, 无则创建
     * 水电, 清理, 赔偿可以直接操作, 房租和物业费需要计算后处理
     * 房租和水电的计算, 计算本月之后需要缴纳的以及实际缴纳的, 然后做差
     */
    private function createOrUpdateCheckOutOrders($record, $bills, $resident, $room, $number) {
        $data = $record->data;

        isset($data['checkout_orders']) ? $orderIds = $data['checkout_orders'] : $orderIds = array();

        foreach ($bills as $type => $money) {
            if (0 < $money) {
                $order = $this->ordermodel->addCheckOutOrderByType(
                    $resident,
                    $room,
                    $number,
                    $this->employee->id,
                    $type,
                    $money,
                    $record->time
                );
                $orderIds[$type] = $order->id;
            } else {
                $orderIds[$type] = 0;
            }
        }

        $data['checkout_orders'] = $orderIds;
        $data['checkout_money']  = $bills;

        $record->data = $data;
        $record->save();
        return true;
    }

    /**
     * 处理退房时需要缴纳的房租和物业
     */
    private function handleRentAndManagement($resident, $record, $number) {
        $checkoutData = $record->data;

        //计算应缴和实缴
        $shouldPay = $this->calcCheckoutMoney($resident, $record->time, $record->type);

        //搜索本月及之后的房租和物业订单, 并计算已支付金额
        $orders    = $this->ordermodel->rentAndPropertyForThisMonthAndLater($resident->id, $record->time);
        $moneyPaid = $this->rentAndManagementPaid($orders);

        $ordersPending = $orders->where('status', Ordermodel::STATE_PENDING);
        $ordersTemp    = $ordersPending->where('year', $record->time->year)->where('month', $record->time->month);

        $orderIds = [];
        if ($shouldPay['rent'] > $moneyPaid['ROOM']) {
            $order = $ordersTemp->where('type', Ordermodel::PAYTYPE_ROOM)->first();
            if (count($order)) {
                $orderIds[]    = $order->id;
                $order->number = $number;
                $order->money  = $shouldPay['rent'] - $moneyPaid['ROOM'];
                $order->paid   = $shouldPay['rent'] - $moneyPaid['ROOM'];
                $order->save();
            } else {
                $order = $this->ordermodel->addCheckOutOrderByType(
                    $resident,
                    $resident->roomunion,
                    $number,
                    $this->employee->id,
                    Ordermodel::PAYTYPE_ROOM,
                    $shouldPay['rent'] - $moneyPaid['ROOM'],
                    $record->time
                );
            }
            $checkoutData['checkout_money']['room']  = $shouldPay['rent'] - $moneyPaid['ROOM'];
            $checkoutData['checkout_orders']['room'] = $order->id;
        } else {
            $checkoutData['checkout_money']['room']  = 0;
            $checkoutData['checkout_orders']['room'] = 0;
        }

        if ($shouldPay['property'] > $moneyPaid['MANAGEMENT']) {
            $order = $ordersTemp->where('type', Ordermodel::PAYTYPE_MANAGEMENT)->first();
            if (count($order)) {
                $orderIds[]    = $order->id;
                $order->number = $number;
                $order->money  = $shouldPay['property'] - $moneyPaid['MANAGEMENT'];
                $order->paid   = $shouldPay['property'] - $moneyPaid['MANAGEMENT'];
                $order->save();
            } else {
                $order = $this->ordermodel->addCheckOutOrderByType(
                    $resident,
                    $resident->roomunion,
                    $number,
                    $this->employee->id,
                    Ordermodel::PAYTYPE_MANAGEMENT,
                    $shouldPay['property'] - $moneyPaid['MANAGEMENT'],
                    $record->time
                );
            }
            $checkoutData['checkout_money']['management']  = $shouldPay['property'] - $moneyPaid['MANAGEMENT'];
            $checkoutData['checkout_orders']['management'] = $order->id;
        } else {
            $checkoutData['checkout_money']['management']  = 0;
            $checkoutData['checkout_orders']['management'] = 0;
        }

        //删除多余的订单
        $ordersPending->whereNotIn('id', $orderIds)->map(function ($order) use ($orderIds) {
            in_array($order->id, $orderIds) ?: $order->delete();
        });

        $record->data = $checkoutData;
        $record->save();

        return true;
    }

    /**
     * 判断账单的截止日期
     * 长租违约, 截止日为退房日, 长租正常退房, 截止日为退租日与合同截止日的较大值
     * 短租, 违约影响不大, 违约的话就是退房日期, 不违约的话就是当月合同截止日与退房日的较大值
     */
    private function calcCheckoutMoney($resident, $checkoutDate, $checkoutType) {
        switch ($resident->rent_type) {
        case Residentmodel::RENTTYPE_LONG:
            return $this->calcCheckoutMoneyLong($resident, $checkoutDate, $checkoutType);
            break;
        case Residentmodel::RENTTYPE_SHORT:
            return $this->calcCheckoutMoneyShort($resident, $checkoutDate, $checkoutType);
            break;
        default:
            throw new \Exception('不存在的租赁类型!');
            break;
        }
    }

    /**
     * 计算到指定日期的费用总和, 长租情况, 应收
     */
    private function calcCheckoutMoneyLong($resident, $checkoutDate, $checkoutType) {
        switch ($checkoutType) {
        case Checkoutmodel::TYPE_NORMAL:
            $endDate = $resident->end_time->lt($checkoutDate) ? $checkoutDate : $resident->end_time;
            break;
        case Checkoutmodel::TYPE_ABNORMAL:
            $endDate = $checkoutDate;
            break;
        default:
            throw new \Exception('不合法的参数值!');
            break;
        }

        //当月的房租计算开始日期, 一般应该是从1号开始计算, 但是万一有入住当月就退房的情况呢?
        $startDay    = $resident->begin_time->lte($endDate->copy()->startOfMonth()) ? 1 : $resident->begin_time->day;
        $daysOfMonth = $endDate->copy()->endOfMonth()->day;

        $rent     = ceil($resident->real_rent_money * ($endDate->day - $startDay + 1) / $daysOfMonth);
        $property = ceil($resident->real_property_costs * ($endDate->day - $startDay + 1) / $daysOfMonth);

        //如果截止日期晚于当月, 则从当月开始, 整月整月的累加
        if ($endDate->year > $checkoutDate->year OR
            $endDate->year == $checkoutDate->year AND $endDate->month > $checkoutDate->month
        ) {
            $months = $endDate->year > $checkoutDate->year ? $endDate->month + 12 - $checkoutDate->month : $endDate->month - $checkoutDate->month;
            $rent += $resident->real_rent_money * $months;
            $property += $resident->real_property_costs * $months;
        }

        return compact('rent', 'property');
    }

    /**
     * 计算短租住户退房时的应缴款
     * 短租满30天即按照一个月来计算
     * 短租的足月按照合同计算, 不足月的按照合同金额的1.2倍计算每天金额
     * 违约的话, 计算到当天, 不违约的话, 计算到最近的合同截止日及当天的最大值
     */
    private function calcCheckoutMoneyShort($resident, $checkoutDate, $checkoutType) {
        switch ($checkoutType) {
        case Checkoutmodel::TYPE_NORMAL:
            //如果是入住当月退房, 则收一个月的房租
            if ($resident->begin_time->year == $checkoutDate->year AND $resident->begin_time->month == $checkoutDate->month) {
                $rent     = $resident->real_rent_money;
                $property = $resident->real_property_costs;
            } else {
                $higherPriceDays = max(0, $checkoutDate->day - $resident->begin_time->day + 1);
                $daysLastMonth   = $resident->begin_time->copy()->endOfMonth()->day;
                $rent            = $resident->real_rent_money -
                ceil(($daysLastMonth - $resident->begin_time->day + 1) * $resident->real_rent_money / $daysLastMonth);
                $property = $resident->real_property_costs - ceil(($daysLastMonth - $resident->begin_time->day + 1) * $resident->real_property_costs / $daysLastMonth);

                //这里用的是房租现在的单价, 可能会存在一些问题
                $higherTotal = ceil($resident->room->rent_money * 1.2 / 30 + $resident->real_property_costs / 30) * $higherPriceDays;
                $rentTemp    = ceil($resident->room->rent_money * 1.2 / 30) * $higherPriceDays;
                $property    = $property + $higherTotal - $rentTemp;
                $rent += $rentTemp;
            }
            break;
        case Checkoutmodel::TYPE_ABNORMAL:
            //短租违约就是退房当月的金额
            $startDay = 1;

            if ($checkoutDate->year == $resident->begin_time->year AND $checkoutDate->month == $resident->begin_time->month) {
                $startDay = $resident->begin_time->day;
            }

            $total    = ceil($resident->room->rent_money * 1.2 / 30 + $resident->real_property_costs / 30) * ($checkoutDate->day - $startDay + 1);
            $property = ceil($resident->real_property_costs / 30) * ($checkoutDate->day - $startDay + 1);
            $rent     = $total - $property;
            break;
        default:
            throw new \Exception('参数类型错误!');
            break;
        }

        return compact('rent', 'property');
    }

    /**
     * 统计本月及之后的住宿和物业订单已缴金额
     */
    private function rentAndManagementPaid($orders) {
        return $orders->whereIn('status', [
            Ordermodel::STATE_COMPLETED,
            Ordermodel::STATE_CONFIRM,
        ])->groupBy('type')->map(function ($items) {
            return $items->sum('money');
        })->union([
            Ordermodel::PAYTYPE_ROOM       => 0,
            Ordermodel::PAYTYPE_MANAGEMENT => 0,
        ]);
    }

    /**
     * 检查是否有需要支付的订单
     * 如果没有需要支付的订单, 直接变成已支付状态
     */
    private function setRecordStatus($resident, $record) {
        $orderCnt = $resident->orders()->where('status', Ordermodel::STATE_PENDING)->count();

        if (0 < $orderCnt) {
            $record->status = Ordermodel::STATE_PENDING;
            $record->save();
        }

        return $record;
    }

    /**
     * 检查退房类型和押金抵扣的选项是否冲突
     */
    private function checkCheckOutType($input) {
        if (Checkoutmodel::TYPE_NORMAL == $input['type'] AND !$input['pay_or_not']) {
            return false;
        }

        return true;
    }

    private function validateStore() {

        return array(

            array(
                'field' => 'room_id',
                'label' => '房间id',
                'rules' => 'required|trim|numeric',
            ),
            array(
                'field' => 'resident_id',
                'label' => '住户id',
                'rules' => 'required|trim|numeric',
            ),
            array(
                'field' => 'pay_or_not',
                'label' => '是否支付欠款',
                'rules' => 'required|trim|in_list[0,1]',
            ),
            array(
                'field' => 'type',
                'label' => '退房类型',
                'rules' => 'required|trim|in_list[NORMAL_REFUND,UNDER_CONTRACT]',
            ),
            array(
                'field' => 'water',
                'label' => '水费',
                'rules' => 'required|trim|numeric',
            ),
            array(
                'field' => 'electricity',
                'label' => '电费',
                'rules' => 'required|trim|numeric',
            ),
            array(
                'field' => 'clean',
                'label' => '垃圾清理费',
                'rules' => 'required|trim|numeric',
            ),
            array(
                'field' => 'compensation',
                'label' => '物品赔偿费',
                'rules' => 'required|trim|numeric',
            ),
            array(
                'field' => 'other_deposit_deduction',
                'label' => '其他押金抵扣金额',
                'rules' => 'required|trim|numeric',
            ),
        );
    }

    private function validateSubmitForApproval() {

        return array(

            array(
                'field' => 'checkout_id',
                'label' => '退房id',
                'rules' => 'required|trim|numeric',
            ),
            array(
                'field' => 'account',
                'label' => '开户人名称',
                'rules' => 'required|trim|max_length[64]',
            ),
            array(
                'field' => 'bank',
                'label' => '开户行名称',
                'rules' => 'required|trim|max_length[128]',
            ),
            array(
                'field' => 'bank_card_number',
                'label' => '银行卡号',
                'rules' => 'required|trim|min_length[16]|max_length[19]',
            ),
            array(
                'field' => 'bank_card_img',
                'label' => '银行卡照片地址',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'employee_remark',
                'label' => '电费',
                'rules' => 'trim',
            ),
        );
    }

    private function validateApprove() {
        return array(
            array(
                'field' => 'checkout_id',
                'label' => '退房id',
                'rules' => 'trim|required|integer',
            ),
            array(
                'field' => 'operator_role',
                'label' => '职位',
                'rules' => 'trim|required|in_list[MANAGER,PRINCIPAL]',
            ),
            array(
                'field' => 'remark',
                'label' => '备注',
                'rules' => 'trim|max_length[128]',
            ),
        );
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

    /**
     * 判断员工是否是运营经理
     */
    private function isPrincipal() {
        return isset($this->employee) && $this->employee->position == 'PRINCIPAL';
    }

    /**
     * 判断员工是否是店长
     */
    private function isManager() {
        return isset($this->employee) && $this->employee->position == 'MANAGER';
    }

}
