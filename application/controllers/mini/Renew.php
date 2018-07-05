<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;
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
//        $store_id   = $this->employee->store_id;
        $store_id   = 1;
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
        $room->resident->contract_begin_time = $room->resident->end_time->format('Y-m-d');

        $this->api_res(0,['renew'=>$room]);

    }

    /**
     * 办理续租
     */
    public function store()
    {

    }


    //续租列表
    public function listRenew()
    {

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
//            $newResident->employee_id           = 99;
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
            $newResident->special_term          = $input['special_term'];
            $newResident->status                = Residentmodel::STATE_NOTPAY;
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
            $resident->roomunion->update(
                [
                    'status'        => Roomunionmodel::STATE_BLANK,
                    'people_count'  => 0,
                    'resident_id'   => 0,
                ]
            );

            $resident->status   = Residentmodel::STATE_RENEWAL;
            $resident->data     = ['new_resident_id'=>$newResident->id];

            $c=$resident->save();

            //原住户信息是否需要更新下 比如end_time

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
                'rules' => 'trim|required|integer',
            ),
            array(
                'field' => 'real_property_costs',
                'label' => '实际物业费',
                'rules' => 'trim|integer|required',
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

}
