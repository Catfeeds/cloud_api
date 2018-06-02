<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/1 0001
 * Time:        17:47
 * Describe:
 */
use Carbon\Carbon;
class Checkout extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();

    }

    /**
     * 显示退房记录列表
     * 如果携带参数status, 则检索该status的记录, 若不携带参数, 则检索未完成的记录
     */
    public function listCheckout()
    {
        $input  = $this->input->post(null,true);
        $store_id   = $this->employee->store_id;
        $where  = ['store_id'=>$store_id];
        $this->load->model('checkoutmodel');
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $this->load->model('residentmodel');
        if(isset($input['status'])){
            $status = [$input['status']];
        }else{
            //$status = $this->allStatus();
            $status = array_diff($this->allStatus(),[Checkoutmodel::STATUS_COMPLETED]);
        }
        $list   = Checkoutmodel::with(['roomunion','store','resident'])->where($where)->whereIn('status',$status)->get();
        if(isset($input['room_number'])){
            $list   = $list->where('roomunion.number',$input['room_number']);
        }
        $this->api_res(0,['checkouts'=>$list]);
    }

    /**
     * 提交新的退房订单
     */
    public function store()
    {
//        $field  = ['room_id','resident_id','pay_or_not','type','water','electricity'
//            ,'clean','compensation','other_deposit_deduction'];
//        $input  = $this->input->post(null,true);
//        $store_id   = $this->employee->store_id;
//        if($this->validationText($this->validateStore())){
//            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
//            return;
//        }
//        $this->load->model('checkoutmodel');
//        $this->load->model('residentmodel');
        $this->load->model('ordermodel');
//        //正常退房 不能押金抵扣，如果押金抵扣了，就一定是违约退房
//        if(!$this->checkCheckOutType($input)){
//            $this->api_res(10025);
//            return;
//        }
//
//        //检查是否已经存在该住户的退房记录
//        $record = Checkoutmodel::where(['resident_id' => $input['resident_id']])->count();
//        if($record>0){
//               $this->api_res(10026);
//               return;
//           }
//
//        $resident    = Residentmodel::where('store_id',$store_id)->findOrFial($input['resident_id']);
//        if($resident->status != Residentmodel::STATE_NORMAL){
//            $this->api_res(10011);
//            return;
//        }
//
//        $checkout    = new Checkoutmodel();
//        $checkout->resident_id  = $input['resident_id'];
//        $checkout->room_id      = $input['room_id'];
//        $checkout->employee_id  = $this->employee->id;
//        $checkout->pay_or_not   = $input['pay_or_not'];
//        $checkout->type         = $input['type'];
//        $checkout->other_deposit_deduction  = $input['other_deposit_deduction'];
//        $checkout->status       = Checkoutmodel::STATUS_UNPAID;
//        $checkout->store_id     = $store_id;
//        $checkout->time         = Carbon::now();
//        $checkout->save();

        $number     = $this->ordermodel->getOrderNumber();

        echo $number;die();



        //$record  = $this->repository->findWhere(['resident_id' => $input['resident_id']]);










        try {
            $this->checkCheckOutType($request);
            $input  = $request->all();

            //检查是否已经存在该住户的退房记录
            $record  = $this->repository->findWhere(['resident_id' => $input['resident_id']]);

            if (count($record)) {
                throw new \Exception('该住户的退房订单已经存在!');
            }

            $resident   = $residentRepo->find($input['resident_id']);

            if ($residentRepo->state_normal != $resident->status) {
                throw new \Exception('住户当前状态不能办理退房!');
            }

            $record     = $this->repository->create([
                'resident_id'               => $input['resident_id'],
                'room_id'                   => $input['room_id'],
                'employee_id'               => $this->authUser->id,
                'pay_or_not'                => $input['pay_or_not'],
                'type'                      => $input['type'],
                'time'                      => Carbon::now(),
                'status'                    => $this->repository->status_unpaid,
                'apartment_id'              => $resident->room->apartment_id,
                'other_deposit_deduction'   => $input['other_deposit_deduction'],
            ]);

            $number     = $orderRepo->getOrderNumber();

            $this->createOrUpdateCheckOutOrders(
                $record,
                $request->only(['water', 'clean', 'electricity', 'compensation']),
                $resident,
                $resident->room,
                $number,
                $orderRepo
            );

            $this->handleRentAndManagement($resident, $record, $number, $residentRepo, $orderRepo);
            $this->setRecordStatus($resident, $record, $orderRepo);

        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return $this->respError($e->getMessage());
        }

        return $this->respSuccess($record, new CheckoutTransformer(), '提交成功!');
    }








    /**
     * 检查退房类型和押金抵扣的选项是否冲突
     */
    private function checkCheckOutType($input)
    {
        if (Checkoutmodel::TYPE_NORMAL == $input['type'] AND !$input['pay_or_not']) {
            return false;
        }

        return true;
    }


    private function validateStore()
    {

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


    private function allStatus()
    {

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
