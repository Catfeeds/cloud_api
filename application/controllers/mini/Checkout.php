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
//            $status = array_diff($this->allStatus(),[Checkoutmodel::STATUS_COMPLETED,Checkoutmodel::STATUS_COMPLETED]);
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
        $field = ['room_id', 'resident_id', 'type','clean', 'compensation', 'other_deposit_deduction',
            /*水电参数*/'coldwater_reading','hotwater_reading','electric_reading','coldwater_image',
                'hotwater_image','electric_image'];
        $input    = $this->input->post(null, true);
        $store_id = $this->employee->store_id;
        if (!$this->validationText($this->validateStore())) {
            $this->api_res(1002, ['error' => $this->form_first_error($field)]);
            return;
        }

        //检查是否已经存在该住户的退房记录
        $record = Checkoutmodel::where(['resident_id' => $input['resident_id']])->count();
        if ($record > 0) {
            $this->api_res(10026);
            return;
        }

        $resident = Residentmodel::where('store_id', $store_id)->where('room_id',$input['room_id'])->find($input['resident_id']);
        if (!$resident) {
            $this->api_res(1007);
            return;
        }
        if ($resident->status != Residentmodel::STATE_NORMAL) {
            $this->api_res(10011);
            return;
        }
        $confirmOrders  = $resident->orders()->where('status',Ordermodel::STATE_CONFIRM)->count();
        if($confirmOrders){
            $this->api_res(10040);
            return;
        }
        $generateOrders = $resident->orders()->where('status',Ordermodel::STATE_GENERATED)->get()->sum('money');
        if ($generateOrders>0) {
            $this->api_res(10041);
            return;
        }

        try {
            DB::beginTransaction();
            //创建退房记录
            $checkout                          = new Checkoutmodel();
            $checkout->resident_id             = $input['resident_id'];
            $checkout->room_id                 = $input['room_id'];
            $checkout->employee_id             = $this->employee->id;
            $checkout->type                    = $input['type'];
            $checkout->other_deposit_deduction = $input['other_deposit_deduction'];
            $checkout->status                  = Checkoutmodel::STATUS_UNPAID;
            $checkout->bank                    = empty($input['bank']) ? '' : $input['bank'];
            $checkout->account                 = empty($input['account']) ? '' : $input['bank'];
            $checkout->bank_card_number        = empty($input['bank_card_number']) ? '' : $input['bank_card_number'];
            $checkout->employee_remark         = isset($input['employee_remark'])?$input['employee_remark']:'';
            $checkout->bank_card_img           = empty($input['bank_card_img']) ? '' : $this->splitAliossUrl($input['bank_card_img']);
            $checkout->store_id                = $store_id;

            $checkout->time = Carbon::now();
            $checkout->save();

            $utlity                 = $this->utility($input);

            $bills['water']         = $utlity['water'];
            $bills['hot_water']     = $utlity['hot_water'];
            $bills['electricity']   = $utlity['electric'];
            $bills['clean']         = $input['clean'];
            $bills['compensation']  = $input['compensation'];
            //生成退房时的订单
            $this->createOrUpdateCheckOutOrders(
                $checkout,
                $bills,
                $resident,
                $resident->roomunion
            );
            //重置原房间状态
            $resident->roomunion->update(
                [
                    'status'       => Roomunionmodel::STATE_BLANK,
                    'people_count' => 0,
                    'resident_id'  => 0,
                ]
            );

            Residentmodel::where('id', $input['resident_id'])->update(['status' => 'CHECKOUT']);
            $taskflow_id   = $this->createTaskflow($checkout->room_id,'CHECKOUT');
            if ($taskflow_id) {
                $checkout->taskflow_id  = $taskflow_id;
                $checkout->status  = 'AUDIT';
                $checkout->save();
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
        $this->api_res(0, ['checkout_id' => $checkout->id]);
    }

    /**
     * 提交给店长审核
     */

    /**
     * 退房计算水电费用
     */
    private function utility($post)
    {
        $this->load->model('Meterreadingtransfermodel');
        $this->load->model('roomunionmodel');
        $this->load->model('smartdevicemodel');
        $year           = date('Y');
        $month          = date('m');
        $last_coldwater = Meterreadingtransfermodel::where('year',$year)->where('month',$month)->where('resident_id',$post['resident_id'])->where('room_id',$post['room_id'])->where('status',Meterreadingtransfermodel::NORMAL)->where('type',Meterreadingtransfermodel::TYPE_WATER_C)->first(['this_reading']);
        $last_hotwater  = Meterreadingtransfermodel::where('year',$year)->where('month',$month)->where('resident_id',$post['resident_id'])->where('room_id',$post['room_id'])->where('status',Meterreadingtransfermodel::NORMAL)->where('type',Meterreadingtransfermodel::TYPE_WATER_H)->first(['this_reading']);
        $last_electric  = Meterreadingtransfermodel::where('year',$year)->where('month',$month)->where('resident_id',$post['resident_id'])->where('room_id',$post['room_id'])->where('status',Meterreadingtransfermodel::NORMAL)->where('type',Meterreadingtransfermodel::TYPE_ELECTRIC)->first(['this_reading']);
        if ($month      == 12){
            $month      = 1;
            $year       = $year+1;
        }else{
            $month      = $month+1;
        }
        $roomunion      = Roomunionmodel::where('id',$post['room_id'])->first(['building_id','store_id']);
        $building_id    = $roomunion->building_id;
        $store_id       = $roomunion->store_id;
        $price          = Storemodel::where('id',$store_id)->first(['id','water_price','hot_water_price','electricity_price']);
        $cold_water     = Smartdevicemodel::where('room_id',$post['room_id'])->where('type',Meterreadingtransfermodel::TYPE_WATER_C)->first(['serial_number']);
        $hot_water      = Smartdevicemodel::where('room_id',$post['room_id'])->where('type',Meterreadingtransfermodel::TYPE_WATER_H)->first(['serial_number']);
        $electric       = Smartdevicemodel::where('room_id',$post['room_id'])->where('type',Meterreadingtransfermodel::TYPE_ELECTRIC)->first(['serial_number']);
        if (empty($cold_water)){
            $cold_water_number  = '';
        }else{
            $cold_water_number  =$cold_water->serial_number;
        }
        if (empty($hot_water)){
            $hot_water_number  = '';
        }else{
            $hot_water_number  =$hot_water->serial_number;
        }
        if (empty($electric)){
            $electric_number  = '';
        }else{
            $electric_number  =$electric->serial_number;
        }
        //上传冷水表读数
        $coldwater      = new Meterreadingtransfermodel();
        $arr_coldwater  = [
            'store_id'      => $store_id,
            'building_id'   => $building_id,
            'serial_number' => $cold_water_number,
            'room_id'       => $post['room_id'],
            'resident_id'   => $post['resident_id'],
            'year'          => $year,
            'month'         => $month,
            'type'          => Meterreadingtransfermodel::TYPE_WATER_C,
            'this_reading'  => floatval($post['coldwater_reading']),
            'image'         => $post['coldwater_image'],
            'this_time'     => date('Y-m-d H:i:s'),
            'status'        => Meterreadingtransfermodel::NORMAL,
        ];
        $coldwater->fill($arr_coldwater);
        if ($coldwater->save()) {
            $money['water'] = (floatval($post['coldwater_reading']) - $last_coldwater->this_reding) * $price->water_price;
            if (0.01 > $money['water']) {
                return null;
            }
        }
        //上传电表读数
        $electric       = new Meterreadingtransfermodel();
        $arr_electric   = [
            'store_id'      => $store_id,
            'building_id'   => $building_id,
            'serial_number' => $electric_number,
            'room_id'       => $post['room_id'],
            'resident_id'   => $post['resident_id'],
            'year'          => $year,
            'month'         => $month,
            'type'          => Meterreadingtransfermodel::TYPE_ELECTRIC,
            'this_reading'  => floatval($post['electric_reading']),
            'image'         => $post['electric_image'],
            'this_time'     => date('Y-m-d H:i:s'),
            'status'        => Meterreadingtransfermodel::NORMAL,
        ];
        $electric->fill($arr_electric);
        if($electric->save()){
            $money['electric']      = (floatval($post['electric_reading']) - $last_electric->this_reading) * $price->electricity_price;
            if (0.01 > $money['electric']) {
                return null;
            }
        }
        //上传热水表读数
        if (isset($post['hotwater_reading'])&&!empty($post['hotwater_reading'])){
            $hotwater       = new Meterreadingtransfermodel();
            $arr_hotwater   =[
                'store_id'      => $store_id,
                'building_id'   => $building_id,
                'serial_number' => $hot_water_number,
                'room_id'       => $post['room_id'],
                'resident_id'   => $post['resident_id'],
                'year'          => $year,
                'month'         => $month,
                'type'          => Meterreadingtransfermodel::TYPE_WATER_H,
                'this_reading'  => floatval($post['hotwater_reading']),
                'image'         => $post['hotwater_image'],
                'this_time'     => date('Y-m-d H:i:s'),
                'status'        => Meterreadingtransfermodel::NORMAL,
            ];
            $hotwater->fill($arr_hotwater);
            if($hotwater->save()){
                $money['hot_water']     = (floatval($post['hotwater_reading']) - $last_hotwater->this_reading) * $price->hot_water_price;
                if (0.01 > $money['hot_water']) {
                    return null;
                }
            }
        }
        /*var_dump($arr_coldwater);
        var_dump($arr_electric);
        var_dump($arr_hotwater);*/
        return $money;

    }

    /**
     * 显示退房记录的详情
     * 根据记录的状态不同, 显示不同的信息
     * 未提交审核前, 调取相关表查询数据
     */
    public function show() {

        $input                         = $this->input->post(null, true);
        empty($input['checkId']) ? $id = '' : $id = $input['checkId'];
        if (empty($id)) {
            $this->api_res(1007);
            return;
        }
        $checkout = Checkoutmodel::find($id);
        if (empty($checkout)) {
            $this->api_res(1007);
            return;
        }

        $resident               = Residentmodel::find($checkout->resident_id);
        $begin_time             = $resident->begin_time->format('Y-m-d');
        $end_time               = $resident->end_time->format('Y-m-d');
        $resident               = $resident->toArray();
        $resident['begin_time'] = $begin_time;
        $resident['end_time']   = $end_time;

        $data['checkout'] = $checkout->toArray();
        $data['resident'] = $resident;
        $data['room']     = Roomunionmodel::find($checkout->room_id)->toArray();
        $data['orders']   = Ordermodel::where('resident_id', $checkout->resident_id)->where('sequence_number', '')->get()->toArray();

        $this->api_res(0, $data);

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

    /**
     * 创建或更新退房时的账单, 包括水费, 电费, 垃圾清理费, 物品赔偿费以及需补交的房租和物业费
     * 有则更新, 无则创建
     * 水电, 清理, 赔偿可以直接操作, 房租和物业费需要计算后处理
     * 房租和水电的计算, 计算本月之后需要缴纳的以及实际缴纳的, 然后做差
     */
    private function createOrUpdateCheckOutOrders($record, $bills, $resident, $room) {
        $data = $record->data;

        isset($data['checkout_orders']) ? $orderIds = $data['checkout_orders'] : $orderIds = array();

        foreach ($bills as $type => $money) {
            if (0 < $money) {
                $order = $this->ordermodel->addCheckOutOrderByType(
                    $resident,
                    $room,
                    $this->employee->store_id,
                    $this->employee->id,
                    $type,
                    $money,
                    Carbon::now()
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
                $rent            = $resident->real_rent_money - ceil(($daysLastMonth - $resident->begin_time->day + 1) * $resident->real_rent_money / $daysLastMonth);
                $property        = $resident->real_property_costs - ceil(($daysLastMonth - $resident->begin_time->day + 1) * $resident->real_property_costs / $daysLastMonth);

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
                'field' => 'type',
                'label' => '退房类型',
                'rules' => 'required|trim|in_list[NORMAL_REFUND,UNDER_CONTRACT]',
            ),
/*            array(
                'field' => 'water',
                'label' => '水费',
                'rules' => 'required|trim|numeric',
            ),
            array(
                'field' => 'electricity',
                'label' => '电费',
                'rules' => 'required|trim|numeric',
            ),*/
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
            //退房水电参数'coldwater_reading','hotwater_reading','electric_reading','coldwater_image',
            //'hotwater_image','electric_image'
            array(
                'field' => 'coldwater_reading',
                'label' => '冷水表读数',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'hotwater_reading',
                'label' => '热水表读数',
                'rules' => 'trim',
            ),
            array(
                'field' => 'electric_reading',
                'label' => '电表读数',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'coldwater_image',
                'label' => '冷水表照片',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'hotwater_image',
                'label' => '热水表照片',
                'rules' => 'trim',
            ),
            array(
                'field' => 'electric_image',
                'label' => '电表照片',
                'rules' => 'required|trim',
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

    /**
     * 创建退房的同时创建退款的任务流
     */
    private function createTaskflow($room_id,$type)
    {
        $this->load->model('taskflowmodel');
        $this->load->model('taskflowtemplatemodel');
        $this->load->model('taskflowstepmodel');
        $this->load->model('taskflowsteptemplatemodel');
        $template   = Taskflowtemplatemodel::where('company_id',COMPANY_ID)
            ->where('type',$type)
            ->first();
        if (empty($template)) {
            return false;
        }
        $step_field = ['id','company_id','name','type','seq','position_ids','employee_ids'];
        $step_template  = $template->step_template()->get($step_field);

        $taskflow   = new Taskflowmodel();
        $taskflow->fill($template->toArray());
        $taskflow->template_id  = $template->id;
        $taskflow->serial_number= $taskflow->newNumber($this->employee->store_id);
        $taskflow->store_id     = $this->employee->store_id;
        $taskflow->create_role  = Taskflowmodel::CREATE_EMPLOYEE;
        $taskflow->employee_id  = $this->employee->id;
        $taskflow->status       = Taskflowmodel::STATE_AUDIT;
        $taskflow->room_id      = $room_id;
        $taskflow->save();
        $step_template_keys_transfer = ['step_template_id','company_id','name','type','seq','position_ids','employee_ids'];
        $step_template_arr  = $step_template->toArray();
        $step_merge_data = [
            'store_id'      => $this->employee->store_id,
            'taskflow_id'   => $taskflow->id,
            'status'        => Taskflowstepmodel::STATE_AUDIT,
            'created_at'    => Carbon::now()->toDateTimeString(),
            'updated_at'    => Carbon::now()->toDateTimeString(),
        ];
        $result = [];
        foreach ($step_template_arr as $step){
            $step_combine   = array_combine($step_template_keys_transfer,$step);
            $result[]   = array_merge($step_merge_data,$step_combine);
        }
        Taskflowstepmodel::insert($result);

        return $taskflow->id;
    }

    /**
     * 重新发起新的退款的任务流（暂时搁置）
     */
    public function renewTaskflow()
    {
        exit;
        $input  = $this->input->post(null,true);
        $checkout_id    = $input['checkout_id'];
        //检查checkout的状态是不是AUDIT才可以发起任务流
        $checkout   = Checkoutmodel::findOrFail($checkout_id);
        if ($checkout->status != Checkoutmodel::STATUS_AUDIT) {
            $this->api_res(10201);
            return;
        }
        $this->load->model('taskflowmodel');
        //如果有已经存在的任务流，需要先去任务流关闭
        if ($checkout->taskflow->status != Taskflowmodel::STATE_CLOSED) {
            $this->api_res(10202);
            return;
        }
        //创建新的任务流
        try {
            DB::beginTransaction();
            $taskflow_id   = $this->createTaskflow($checkout->room_id,'CHECKOUT');
            $checkout->taskflow_id  = $taskflow_id;
            $checkout->employee_id  = $this->employee->id;
            $b=$checkout->save();
            if ($taskflow_id>0 && $b) {
                DB::commit();
            } else {
                DB::rollBack();
            }
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
        $this->api_res(0);
    }

}
