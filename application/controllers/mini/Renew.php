<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/7/4 0004
 * Time:        14:20
 * Describe:    续租
 */
class Renew extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    //通过房间号获取住户信息
    public function getResidentByRoom()
    {
        $input  = $this->input->post(null,true);
        $room_number    = $input['room_number'];
//        $status   = $input['status'];
        $store_id   = $this->employee->store_id;
//        $store_id   = 1;
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $where  = [
            'store_id'=>$store_id,
            'number'=>$room_number,
        ];

        $room   = Roomunionmodel::with('resident')
            ->where($where)
            ->first();
        if(empty($room))
        {
            $this->api_res(1007);
            return;
        }
        if(empty($room->resident) || $room->status!=Roomunionmodel::STATE_RENT){
            $this->api_res(10035);
            return;
        }

        $room->resident->contract_begin_time = $room->resident->begin_time->format('Y-m-d');
        $room->resident->contract_end_time = $room->resident->end_time->format('Y-m-d');

        $this->api_res(0,['renew'=>$room]);

    }

    /**
     * 获取续租的房间状态，如果不换房那么状态应该是RENT 否则应该是BLANK
     */
    public function getRenewRoomStatus()
    {
        $input  = $this->input->post(null,true);
        $room_number    = $input['room_number'];
        $resident_id    = $input['resident_id'];
        $store_id       = $this->employee->store_id;
        $where  = [
            'number'    => $room_number,
            'store_id'  => $store_id,
        ];
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $this->load->model('ordermodel');

        //1.找到该住户所在的房间
        $org_room   = Roomunionmodel::with('resident')
            ->where('resident_id',$resident_id)
            ->first();

        if(empty($org_room) || empty($org_room->resident || $org_room->status!=Roomunionmodel::STATE_RENT)){
            $this->api_res(10036);
            return;
        }

        //检测住户是否有未完成账单
        if(!$this->checkUnfinishedBills($org_room->resident)){
            $this->api_res(10023);
            return;
        }

        //需要入住的房间
        $room   = Roomunionmodel::with('resident')->where($where)->first();
        if(!$room){
            $this->api_res(1007);
            return;
        }

        //如果不是在原房间续租
        if($room->id    != $org_room->id)
        {
            if($room->status!=Roomunionmodel::STATE_BLANK){
                $this->api_res(10010);
                return;
            }
        }
        $this->api_res(0,$room);
    }



    /**
     * 续租列表
     */
    public function listRenew()
    {
        $input  = $this->input->post(null,true);
//        $page   = $input['page'];
//        $store_id   = 1;
        $store_id   = $this->employee->store_id;
//        $per_page   = $input['per_page'];
        $where  = ['store_id'=>$store_id];
//        empty($input['room_number'])?:$where['number']=$input['room_number'];
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $this->load->model('ordermodel');
        $this->load->model('customermodel');
        $rooms  = Roomunionmodel::with(['resident'=>function($q){
            $q->with('customer');

        }])
            ->where($where)
            ->whereHas('resident',function($a){
                $a->where('status',Residentmodel::STATE_NOTPAY)
                    ->where('type',Residentmodel::TYPE_RENEWAL);
            })
            ->get();

        $a  = [];
        foreach ($rooms as $room){
            $a[]  = $room;
        }
        $this->api_res(0,$a);
    }

    /**
     * 取消办理续租
     */
    public function destroy()
    {
        $input  = $this->input->post(null,true);
        $resident_id    = $input['resident_id'];
        $this->load->model('residentmodel');
        $this->load->model('roomunionmodel');
        $this->load->model('ordermodel');
        $resident   = Residentmodel::find($resident_id);
        $room   = $resident->roomunion;
        //判断resident是不是续租，并且未支付，并且没有已经支付的账单
        if($resident->type!==Residentmodel::TYPE_RENEWAL || $resident->status!=Residentmodel::STATE_NOTPAY)
        {
            $this->api_res(10024);
            return;
        }

        $paidOrders = $this->getResidentPaidOrders($resident);

        //var_dump($paidOrders);exit;

        if($paidOrders){

            $this->api_res(10015);
            return;
        }

        $org_resident_id    = $resident->data['org_resident_id'];


        try{
            DB::beginTrascation();


            DB::rollBack();
//            DB::commit();
        }catch (Exception $e){
            DB::rollBack();
            throw $e;
        }

        //DB

        //根据原住户的end_time 判断是不是还原房间状态为rent 是否还原住户状态，添加data 取消办理

        //删除新住户 和新住户的合同 以及账单

        //取消成功

    }

    /**
     * 获取住户已经支付的账单
     */
    private function getResidentPaidOrders($resident){

        $orders =$resident->orders()
            ->whereIn('status',[Ordermodel::STATE_CONFIRM,Ordermodel::STATE_COMPLETED])
            ->get()
            ->toArray();

        return $orders;

    }

    /**
     * 住户续租
     */
    public function renew()
    {
        $field  = [
            'room_id','begin_time','people_count','contract_time','discount_id','first_pay_money',
            'deposit_money','deposit_month','tmp_deposit','rent_type','pay_frequency',
            'special_term','remark','real_rent_money','real_property_costs',
        ];
        $input  = $this->input->post(null,true);
        if(!$this->validationText($this->validateRenewRequest())){
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $store_id   = $this->employee->store_id;
//        $store_id   = 1;
        $this->load->model('residentmodel');
        $resident   = Residentmodel::where(['store_id'=>$store_id])->findOrFail($input['resident_id']);
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $this->load->model('ordermodel');
        $roomunion  = Roomunionmodel::where(['store_id'=>$store_id])->findOrFail($input['room_id']);
        $store  = $roomunion->store;

        if(0 <$roomunion->resident_id && $input['resident_id'] != $roomunion->resident_id){
            $this->api_res(10022);
            return;
        }

        if($resident->room_id != $input['room_id']){
            if(!$roomunion->isBlank()){
                $this->api_res(10010);
                return;
            }
        }

        if (Residentmodel::STATE_NORMAL != $resident->status) {
            $this->api_res(10024);
            return;
        }

        if(!$this->checkUnfinishedBills($resident)){
            $this->api_res(10023);
            return;
        }

        $orgInfo    = $resident->toArray();
        array_forget($orgInfo, ['id', 'discount_id', 'customer_id', 'uxid','created_at']);

        //住户的个人信息, 用之前的记录填充
        try{

            DB::beginTransaction();
            $newResident                        = new Residentmodel();
            $newResident->fill($orgInfo);
            $newResident->employee_id           = $this->employee->id;
            $newResident->store_id              = $this->employee->store_id;
            $newResident->company_id            = $this->employee->company_id;
//            $newResident->employee_id           = 1;
//            $newResident->store_id              = 1;
//            $newResident->company_id            = 1;
            $newResident->room_id               = $input['room_id'];
            $newResident->begin_time            = $input['begin_time'];
            $newResident->end_time              = $this->residentmodel->contractEndDate($input['begin_time'], $input['contract_time']);
            $newResident->pay_frequency         = $input['pay_frequency'];
            $newResident->real_rent_money       = $input['real_rent_money'];
            $newResident->real_property_costs   = $input['real_property_costs'];
            $newResident->water_price           = $store->water_price;
            $newResident->hot_water_price       = $store->hot_water_price;
            $newResident->electricity_price     = $store->electricity_price;
            $newResident->discount_id           = $input['discount_id'];
            $newResident->first_pay_money       = $input['first_pay_money'];
            $newResident->contract_time         = $input['contract_time'];
            $newResident->rent_type             = $input['rent_type'];
            $newResident->remark                = isset($input['remark'])?$input['remark']:'无';
            $newResident->deposit_month         = max($resident->deposit_month, $input['deposit_month']);
            $newResident->deposit_money         = max($resident->deposit_money, $input['deposit_money']);
            $newResident->tmp_deposit           = max($resident->tmp_deposit, $input['tmp_deposit']);
            $newResident->rent_price           = $roomunion->rent_price;
            $newResident->property_price           = $roomunion->property_price;
//            $newResident->special_term          = $input['special_term'];
            $newResident->status                = Residentmodel::STATE_NOTPAY;
            $newResident->type                  = Residentmodel::TYPE_RENEWAL;
            $newResident->data                  = [
                'org_resident_id'   => $resident->id,
                'renewal'           => [
                    'delt_other_deposit'    => max(0, $input['tmp_deposit'] - $resident->tmp_deposit),
                    'delt_rent_deposit'     => max(0, ceil($input['deposit_money'] - $resident->deposit_money)),
                ],
            ];
            $a=$newResident->save();

//          发放优惠券
//            if ($request->has('normal_discount_ids')) {
//                $actRepo->assignCheckInCoupons($resident, $request->input('normal_discount_ids'));
//            }

            //重置原房间状态
            //应该先把原房间改为占用？(取消办理)
            $resident->roomunion->update(
                [
                    'status'        => Roomunionmodel::STATE_BLANK,
                    'people_count'  => 0,
                    'resident_id'   => 0,
                ]
            );

            $resident->status   = Residentmodel::STATE_NORMAL;
//            $resident->status   = Residentmodel::STATE_RENEWAL;
            $resident->data     = ['new_resident_id'=>$newResident->id];

            $c=$resident->save();

            //原住户信息是否需要更新下 比如end_time，不需要

            $b=$this->occupiedByResident($roomunion, $newResident);

            if($a&&$b&&$c){
                DB::commit();
            }else{
                DB::rollBack();
                $this->api_res(1009);
                return;
            }

        }catch (Exception $e){
            DB::rollBack();
            throw $e;
        }

        $this->api_res(0,['resident_id'=>$newResident->id]);
    }

    /**
     * 查询住户是否有未完成的账单
     */
    private function checkUnfinishedBills($resident)
    {
        $query  = $resident->orders()->whereIn('status', [
            Ordermodel::STATE_PENDING,
            Ordermodel::STATE_AUDITED,
            Ordermodel::STATE_GENERATED,
            Ordermodel::STATE_CONFIRM,
        ]);

        if ($query->exists()) {
            return false;
        }

        return true;
    }

    private function validateRenewRequest()
    {
        return array(
            array(
                'field' => 'resident_id',
                'label' => '住户id',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'room_id',
                'label' => '房间号',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'begin_time',
                'label' => '开始时间',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'contract_time',
                'label' => '合同时长',
                'rules' => 'required|trim|integer',
            ),
            array(
                'field' => 'discount_id',
                'label' => '折扣id',
                'rules' => 'trim|integer',
            ),
            array(
                'field' => 'rent_type',
                'label' => '出租类型',
                'rules' => 'trim|required|in_list[LONG,SHORT]',
            ),
            array(
                'field' => 'real_rent_money',
                'label' => '实际租金',
                'rules' => 'trim|required|numeric',
            ),
            array(
                'field' => 'real_property_costs',
                'label' => '实际物业费',
                'rules' => 'trim|required|numeric',
            ),
            array(
                'field' => 'first_pay_money',
                'label' => '首次支付',
                'rules' => 'trim|numeric|required',
            ),
            array(
                'field' => 'pay_frequency',
                'label' => '付款周期',
                'rules' => 'trim|numeric|required',
            ),
            array(
                'field' => 'deposit_money',
                'label' => '押金',
                'rules' => 'trim|numeric|required',
            ),
            array(
                'field' => 'deposit_month',
                'label' => '押金月份',
                'rules' => 'trim|integer|required',
            ),
            array(
                'field' => 'tmp_deposit',
                'label' => '其他押金',
                'rules' => 'trim|numeric|required',
            ),
            array(
                'field' => 'remark',
                'label' => '备注',
                'rules' => 'trim',
            ),
            array(
                'field' => 'special_term',
                'label' => '合同中的特别说明',
                'rules' => 'trim',
            ),
        );
    }

    /**
     * 办理入住时, 将房间状态更新为占用状态
     */
    public function occupiedByResident($room, $resident, $status = Roomunionmodel::STATE_OCCUPIED)
    {
        /*if (!in_array($room->status, [Roomunionmodel::STATE_RESERVE, Roomunionmodel::STATE_BLANK])) {

            throw new \Exception('房间当前状态无法办理!');
        }

        if (Roomunionmodel::STATE_RESERVE == $room->status AND $room->resident_id != $resident->id) {

            throw new \Exception('该房间已经被其他人预约了!');
        }

        if (!in_array($status, [Roomunionmodel::STATE_OCCUPIED, Roomunionmodel::STATE_RESERVE])) {

            throw new \Exception('status 参数不合法!');
        }*/

        return $room->update([
            'status'        => $status,
            'resident_id'   => $resident->id,
            'begin_time'    => $resident->begin_time,
            'end_time'      => $resident->end_time,
            'people_count'  => $resident->people_count ? : 0,
        ]);
    }

}
