<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/10/12 0012
 * Time:        15:44
 * Describe:    新版退房（小程序）
 */
class Checkoutnew extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 退房获取房间信息
     */
    public function getResidentInfo()
    {
        $store_id    = $this->employee->store_id;
        $room_number = $this->input->post('room_number', true);
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $this->load->model('ordermodel');

        $room   = Roomunionmodel::where('store_id',$store_id)
            ->where('number',$room_number)
            ->first();
        $resident   = $room->resident;
        if (!$resident) {
            $this->api_res(1007);
            return;
        }
        $orders  = $resident->orders;
        //检查房间住户信息
        if ($code = $this->checkRoom($room,$resident,$orders)) {
           $this->api_res($code);
           return;
        }
        //住户的押金
        $deposit_rent  = number_format($orders->where('type',Ordermodel::PAYTYPE_DEPOSIT_R)->sum('money'),2,'.','');
        $deposit_other = number_format($orders->where('type',Ordermodel::PAYTYPE_DEPOSIT_O)->sum('money'),2,'.','');
        $begin_time    = Carbon::parse($resident->begin_time)->format('Y-m-d');
        $end_time      = Carbon::parse($resident->end_time)->format('Y-m-d');
        $name          = $resident->name;
        $phone         = $resident->phone;
        $room_id       = $room->id;
        $resident_id   = $resident->id;
//        $refund_time   = date('Y-m-d',time());
//        $reason        = '';
        $this->api_res(0,compact('room_id','resident_id','name','phone','deposit_rent','deposit_other','begin_time','end_time'));
    }

    /**
     * 点击下一步初次计算金额返回给前端展示
     */
    public function showInitMoney()
    {
        $field  = [
            'room_id','resident_id',
            'type','refund_time_e','reason_e','remark_e',
            'utility',
            'check_images'
        ];
        if (!$this->validationText($this->validateInitMoneyRequest())){
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $input  = $this->input->post(null,true);
        $this->load->model('residentmodel');
        $this->load->model('roomunionmodel');
        $this->load->model('ordermodel');
        $this->load->model('checkoutmodel');
        $resident   = Residentmodel::find($input['resident_id']);
        $room       = Roomunionmodel::find($input['room_id']);
        $orders  = $resident->orders;
        //检查房间住户信息
        if ($code = $this->checkRoom($room,$resident,$orders)) {
            $this->api_res($code);
            return;
        }
        //如果type是免责退，检查合同开始时间是否满足条件
       if (!$this->checkType($input['type'],$resident)) {
           $this->api_res(10043);
           return;
       }
        $handle_time    = Carbon::now();
        $refundMoney    = Checkoutmodel::calcInitRefundMoney($input['type'],$room,$resident,$orders,$handle_time,$input['utility'],$this->employee);
        $charge_order   = $refundMoney['charge_order'];
        $spend_order    = $refundMoney['spend_order'];
        $refund_sum     = $refundMoney['refund_sum'];
        $charge_sum     = $refundMoney['charge_sum'];
        $spend_sum      = $refundMoney['spend_sum'];

        $this->api_res(0,[
            'charge_order'  =>$charge_order,
            'spend_order'   =>$spend_order,
            'refund_sum'    =>number_format($refund_sum,2,'.',''),
            'charge_sum'    =>number_format($charge_sum,2,'.',''),
            'spend_sum'     =>number_format($spend_sum,2,'.',''),
            'params'        => $input,
            ]);
    }

    /**
     * 退房-确认验房（确认）
     */
    public function confirmCheck()
    {
        $field  = [
            'room_id','resident_id',
            'type','refund_time_e','reason_e','remark_e',
            'utility',
            'create_orders','give_up','account_info',
            'account','bank_name','bank_card_number','bank_card_front_img','bank_card_back_img',
            'card_front_img','card_back_img',
//            'signature_type','signature_images'
        ];
        //表单验证
        if (!$this->validationText($this->validateInitMoneyRequest())) {
            $this->api_res(1002,['error'=> $field]);
            return;
        }
        if (!$this->validationText($this->validateConfirmRequest())) {
           $this->api_res(1002,['error'=> $field]);
           return;
        }
        //验证房间和住户
        $input  = $this->input->post(null,true);
        $this->load->model('residentmodel');
        $this->load->model('roomunionmodel');
        $this->load->model('ordermodel');
        $resident   = Residentmodel::find($input['resident_id']);
        $room       = Roomunionmodel::find($input['room_id']);
        $orders  = $resident->orders;
        //检查房间住户信息
        if ($code = $this->checkRoom($room,$resident,$orders)) {
            $this->api_res($code);
            return;
        }
        //如果type是免责退，检查合同开始时间是否满足条件
        if (!$this->checkType($input['type'],$resident)) {
            $this->api_res(10043);
            return;
        }
        //办理时间
        $handle_time    = Carbon::now();
        $create_orders  = $input['create_orders'];
        try {
            $this->load->model('checkoutmodel');
            DB::beginTransaction();
            //生成退租单
            $record = $this->createConfirmCheckoutRecord($input);
            // 把水电读数存入记录表
            if (!empty($input['utility'])) {
               $record->utility_readings    = json_encode($input['utility']);
               $record->save();
            }
            if (!empty($create_orders)) {
                $record->add_orders = json_encode($create_orders);
                $record->save();
            }
            //保存验房照片
            $this->storeCheckRoomImage($record,$input['check_images']);
            //重置房间状态
            $room->update(
                [
                    'status'        =>Roomunionmodel::STATE_BLANK,
                    'resident_id'   => 0,
                    'people_count'  => 0,
                    'updated_at'    => Carbon::now(),
                    ]
            );
            //更新住户状态
            Residentmodel::where('id', $input['resident_id'])
                ->update(['status' => 'CHECKOUT','refund_time'=>date('Y-m-d H:i:s'),'updated_at'=>Carbon::now()]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
        $this->api_res(0,['checkout_id'=>$record->id]);
    }

    /**
     * 查看退房详情
     */
    public function showCheckoutDetail()
    {
        $checkout_id    = $this->input->post('checkout_id');
        $this->load->model('checkoutmodel');
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $this->load->model('ordermodel');
        $this->load->model('storemodel');
        $this->load->model('checkoutimagemodel');
        $record         = Checkoutmodel::findOrFail($checkout_id);
        $store          = Storemodel::findOrFail($record->store_id);
        $room           = Roomunionmodel::findOrFail($record->room_id);
        $resident       = Residentmodel::findOrFail($record->resident_id);
        $checkImages    = $this->fullAliossUrl($record->check_images()->pluck('url'),true);
        $orders         = $resident->orders;
        //
        $type           = $record->type;
        $utility_data   = json_decode($record->utility_readings,true);
        $handle_time    = $record->refund_time;
        $employee       = Employeemodel::find($record->employee_id);
        $refundMoney    = Checkoutmodel::calcInitRefundMoney($type,$room,$resident,$orders,$handle_time,$utility_data,$employee);
        $charge_order   = $refundMoney['charge_order'];
        $spend_order    = $refundMoney['spend_order'];
        $refund_sum     = $refundMoney['refund_sum'];
        $charge_sum     = $refundMoney['charge_sum'];
        $spend_sum      = $refundMoney['spend_sum'];
        $create_orders  = json_decode($record->add_orders,true);
        if (!empty($record->account)) {
            $record->back_card_number       = $this->fullAliossUrl($record->back_card_number);
            $record->bank_card_front_img    = $this->fullAliossUrl($record->bank_card_front_img);
            $record->bank_card_back_img     = $this->fullAliossUrl($record->bank_card_back_img);
            $record->card_front_img         = $this->fullAliossUrl($record->card_front_img);
            $record->card_back_img          = $this->fullAliossUrl($record->card_back_img);
        }
        $this->api_res(0,[
            'checkout'=>$record,'store'=>$store,'room'=>$room,'resident'=>$resident,'check_images'=>$checkImages,
            'charge_order'=>$charge_order,'spend_order'=>$spend_order,'create_orders'=>$create_orders,
            'charge_sum'=>$charge_sum,'spend_sum'=>$spend_sum,'refund_sum'=>$refund_sum,
        ]);
    }

    /**
     * 用户签署
     */
    public function Signature()
    {
        $input  = $this->input->post(null,true);
        $field  = [
            'checkout_id','create_orders','give_up',
            'signature_type','signature_image',
        ];
        if (!$this->validationText($this->validateSignature())) {
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $type   = $input['signature_type'];
        switch ($type) {
            case 'NO':
                $this->noSignatureSubmit($input);
                break;
            case 'UNDER':
                $this->underSignatureSubmit($input);
                break;
            case 'ONLINE':
                $this->onlineSignatureSubmit($input);
                break;
            default:
                break;
        }
    }

    /**
     * 小程序退租列表
     */
    public function listRecord()
    {
        $store_id   = $this->employee->store_id;
        $pre_count  = $this->input->post('pre_count',true);
        $page       = $this->input->post('page',true);
        $room_number    = $this->input->post('room_number',true);
        $offset     = ($page-1)*$pre_count;
        $limit      = $pre_count;
        $this->load->model('checkoutmodel');
        $this->load->model('residentmodel');
        $this->load->model('roomunionmodel');
        $status = [
            Checkoutmodel::STATUS_APPLIED,
            Checkoutmodel::STATUS_CONFIRM,
            Checkoutmodel::STATUS_CHECKED,
            Checkoutmodel::STATUS_SIGNING,
            Checkoutmodel::STATUS_SIGNATURE,
            Checkoutmodel::STATUS_AUDIT,
            Checkoutmodel::STATUS_UNPAID,
        ];
        if (empty($room_number)) {
            $room_ids   = Roomunionmodel::where('store_id',$store_id)->pluck('id');
        } else {
            $room_ids   = Roomunionmodel::where('store_id',$store_id)->where('number',$room_number)->pluck('id');
        }

        $count  = Checkoutmodel::with('resident','roomunion')
            ->where('store_id',$store_id)
            ->whereIn('status',$status)
            ->whereIn('room_id',$room_ids)
            ->count();
        $total_page = ceil($count/$pre_count);
        if ($total_page<$page) {
            $this->api_res(0,['total_page'=>$total_page,'count'=>$count,'current_page'=>$page,'list'=>[]]);
            return;
        }
        $records    = Checkoutmodel::with('resident','roomunion')
            ->where('store_id',$store_id)
            ->whereIn('status',$status)
            ->whereIn('room_id',$room_ids)
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function($record){
                $record->type_transfer  = Checkoutmodel::typeTransfer($record->type);
                return $record;
            })
        ;
        $this->api_res(0,['total_page'=>$total_page,'count'=>$count,'current_page'=>$page,'list'=>$records]);
    }

    /**
     * 无法签署提交
     */
    private function noSignatureSubmit($input)
    {
        $this->load->model('checkoutmodel');
        $record = Checkoutmodel::findOrFial($input['checkout_id']);
        $record->add_orders     = json_encode($input['create_orders']);
        $record->give_up        = $input['give_up'];
        $record->signature_type = $input['signature_type'];
        $record->status         = Checkoutmodel::STATUS_SIGNATURE;
        $record->save();

        $record = $this->handleTaskflow($record);
        return $record;
    }

    /**
     * 线下签字提交
     */
    private function underSignatureSubmit($input)
    {
        $this->load->model('checkoutmodel');
        $record = Checkoutmodel::findOrFial($input['checkout_id']);
        $record->add_orders     = json_encode($input['create_orders']);
        $record->give_up        = $input['give_up'];
        $record->signature_type = $input['signature_type'];
        $record->status         = Checkoutmodel::STATUS_SIGNATURE;
        //上传图片，保存地址
        $target = $this->uploadUnderSignature($input['signature_images']);
        $record->signature_url  = $target;
        $record->save();

        $record = $this->handleTaskflow($record);
        return $record;
    }

    /**
     * 选择线上签字
     */
    private function onlineSignatureSubmit($input)
    {
        $this->load->model('checkoutmodel');
        $record = Checkoutmodel::findOrFial($input['checkout_id']);
        $record->add_orders = json_encode($input['create_orders']);
        $record->give_up    = $input['give_up'];
        $record->signature_type  = $input['signature_type'];
        $record->status     = Checkoutmodel::STATUS_SIGNING;
        $record->save();

        //向用户发送签署链接
        $this->onlineSignatureSend($record);
    }

    /**
     * 线上签署-发送住户签字
     */
    private function onlineSignatureSend($record)
    {
        //待开发。。。
        if($record->status!=Checkoutmodel::STATUS_SIGNING){
            return false;
        }
    }

    /**
     * @param $record
     * 处理任务流
     */
    private function handleTaskflow($record){

        $this->load->model('taskflowmodel');
        $this->load->model('storemodel');
        $this->load->model('residentmodel');
        $this->load->model('roomunionmodel');
        $this->load->model('ordermodel');

        $resident   = $record->resident;
        $room       = $record->roomunion;
        $store      = $room->store;
        $msg    = [
            'store_name'    => $store->name,
            'number'        => $room->number,
            'name'          => $resident->name,
            'create_name'   => $this->employee->name,
            'phone'         => $resident->phone,
        ];

        $refund = Checkoutmodel::calcRefundMoneyByRecord($record);
        $refund_sum = $refund['refund_sum'];
        //生成退款任务流
        $taskflow_id  = $this->createTaskflow($record,$record->type,$record->give_up,$refund_sum,$msg);
        if (!empty($taskflow_id)) {
            $record->taskflow_id = $taskflow_id;
            $record->status = Checkoutmodel::STATUS_AUDIT;
            $record->save();
        } else {
            $record->status = Checkoutmodel::STATUS_UNPAID;
            $record->save();
            //如果没有任务流就直接根据记录生成账单
            Checkoutmodel::handleCheckoutOrder($record);
        }
        return $record;
    }


    /**
     * 验证用户签署
     */
    private function validateSignature()
    {
        return array(
            array(
                'field' => 'checkout_id',
                'label' => '选择退房',
                'rules' => 'required|trim|numeric',
            ),
            array(
                'field' => 'create_orders[]',
                'label' => '新增的账单',
                'rules' => 'trim',
            ),
            array(
                'field' => 'give_up',
                'label' => '是否放弃收益',
                'rules' => 'required|trim|integer|in_list[0,1]',
            ),
            array(
                'field' => 'signature_type',
                'label' => '签署类型',
                'rules' => 'trim|required|in_list[NO,UNDER,ONLINE]',
            ),
            array(
                'field' => 'signature_image[]',
                'label' => '签署图片',
                'rules' => 'trim',
            ),
        );

    }

    /**
     * 退房时上传用户线下的签字
     */
    private function uploadUnderSignature($images)
    {
        $url    = 'http://api.i.funxdata.com/v1/strawberry/generate_pdf';
        $target = '/'.date('Y-m-d',time()).'/'.uniqid().'.pdf';
        $params = json_encode([
            'images'    => $images,
            'target'    => $target,
            'env'       => config_item('bucket'),
        ]);
        $this->httpCurl($url,'post','',$params,'application/json');
        return $target;
    }


    /**
     * 生成退房记录
     */
    private function createConfirmCheckoutRecord($input)
    {
        $record = new Checkoutmodel();
        $record->store_id   = $this->employee->store_id;
        $record->room_id    = $input['room_id'];
        $record->resident_id= $input['resident_id'];
        $record->employee_id= $this->employee->id;
        $record->status     = Checkoutmodel::STATUS_CONFIRM;
        $record->type       = $input['type'];
        $record->refund_time_e  = $input['refund_time_e'];  //员工填写的退租时间
        $record->reason_e   = $input['reason_e'];
        $record->remark_e   = $input['remark_e'];
        $record->give_up    = $input['give_up'];
        $record->refund_time= Carbon::now();                //办理退租的时间
        if ($input['account_info']==1) {
            $record->bank       = $input['bank_name'];
            $record->account    = $input['account'];
            $record->back_card_number       = $this->splitAliossUrl($input['back_card_number']);
            $record->bank_card_front_img    = $this->splitAliossUrl($input['bank_card_front_img']);
            $record->bank_card_back_img     = $this->splitAliossUrl($input['bank_card_back_img']);
            $record->card_front_img         = $this->splitAliossUrl($input['card_front_img']);
            $record->card_back_img          = $this->splitAliossUrl($input['card_back_img']);
        }
        $record->save();
        return $record;
    }

    /**
     * 生成退房时的账单
     */
    private function createCheckoutUtilityOrder($record,$room,$resident,$bills)
    {
        $now    = Carbon::now();
        $orders = [];
        foreach ($bills as $key=>$bill) {
            $order  = [
                'number'=>Ordermodel::newNumber(),
                'store_id'  => $resident->store_id,
                'company_id'=> $resident->company_id,
                'room_id'   => $resident->room_id,
                'customer_id'   => $resident->customer_id,
                'uxid'      => $resident->uxid,
                'employee_id'   => $this->employee->id,
                'room_type_id'  => $room->room_type_id,
                'money'     => $bill,
                'paid'      => $bill,
                'type'      => $key ,
                'year'      => $now->year,
                'month'     => $now->month,
                'status'    => 'PENDING',
                'pay_status'=> 'RENEWALS',
                'begin_time'=> $now->copy()->startOfMonth()->format('Y-m-d'),
                'end_time'  => $now->copy()->endOfMonth()->format('Y-m-d'),
                'tag'       => 'CHECKOUT',
            ];
            $orders[]   = $order;
        }
        return $orders;
    }


    /**
     * 生成退房的任务流
     */
    private function createTaskflow($checkout,$type,$give_up,$refund_money,$msg)
    {
        if ($give_up==1) {
            //放弃收益走放弃收益的任务流
            $msg['type']='放弃收益';
            $msg = json_encode($msg);
            $taskflow_id   = $this->taskflowmodel->createTaskflow($this->company_id,Taskflowmodel::TYPE_GIVE_UP,$this->employee->store_id,$checkout->room_id,Taskflowmodel::CREATE_EMPLOYEE,$this->employee->id,$checkout->id,null,$msg);
        } else {
            if ($type=='NORMAL_REFUND') {
                //正常退房走正常退房的任务流
                $msg['type']='正常';
                $msg = json_encode($msg);
                $taskflow_id   = $this->taskflowmodel->createTaskflow($this->company_id,Taskflowmodel::TYPE_CHECKOUT,$this->employee->store_id,$checkout->room_id,Taskflowmodel::CREATE_EMPLOYEE,$this->employee->id,$checkout->id,null,$msg);
            } elseif ($type=='NO_LIABILITY') {
                //三天免责走三天免责的任务流
                $msg['type']='免责';
                $msg = json_encode($msg);
                $taskflow_id   = $this->taskflowmodel->createTaskflow($this->company_id,Taskflowmodel::TYPE_CHECKOUT_NO_LIABILITY,$this->employee->store_id,$checkout->room_id,Taskflowmodel::CREATE_EMPLOYEE,$this->employee->id,$checkout->id,null,$msg);
            } elseif ($type=='UNDER_CONTRACT') {
                if($refund_money>0){
                    //违约退房退款大于0
                    $msg['type']='违约退款金额小于0';
                    $msg = json_encode($msg);
                    $taskflow_id   = $this->taskflowmodel->createTaskflow($this->company_id,Taskflowmodel::TYPE_CHECKOUT_UNDER_CONTRACT,$this->employee->store_id,$checkout->room_id,Taskflowmodel::CREATE_EMPLOYEE,$this->employee->id,$checkout->id,null,$msg);
                }else{
                    //违约退房退款小于0
                    $msg['type']='违约退款金额大于0';
                    $msg = json_encode($msg);
                    $taskflow_id   = $this->taskflowmodel->createTaskflow($this->company_id,Taskflowmodel::TYPE_CHECKOUT_UNDER_CONTRACT_LESS,$this->employee->store_id,$checkout->room_id,Taskflowmodel::CREATE_EMPLOYEE,$this->employee->id,$checkout->id,null,$msg);

                }
            }else{
                $taskflow_id = null;
            }
        }
        return $taskflow_id;
    }



    /**
     * 生成退房时的水电读数记录
     */
    private function utilityRecord($input){
        $arr    = [];
        if (!empty($input['coldwater_reading'])) {
            $arr[]  = [
                'type' => 'COLDWATER',
                'coldwater_reading'   => $input['coldwater_reading'],
                'time'                => date('Y-m-d H:i:s',time()),
                'coldwater_image'     => $input['coldwater_image']
            ];
        }
        if (!empty($input['hotwater_reading'])) {
            $arr[]  = [
                'type'               => 'HOTWATER',
                'hotwater_reading'   => $input['hotwater_reading'],
                'time'               => date('Y-m-d H:i:s',time()),
                'hotwater_image'     => $input['hotwater_image']
            ];
        }
        if (!empty($input['electric_reading'])) {
            $arr[]  = [
                'type'              => 'ELECTRIC',
                'electric_reading'  => $input['electric_reading'],
                'time'              => date('Y-m-d H:i:s',time()),
                'electric_image'    => $input['electric_image']
            ];
        }
        return $arr;
    }

    /**
     * 验证确认验房的提交信息
     */
    private function validateConfirmRequest()
    {
        return array(
            array(
                'field' => 'create_orders[]',
                'label' => '新增的账单',
                'rules' => 'trim',
            ),
            array(
                'field' => 'give_up',
                'label' => '是否放弃收益',
                'rules' => 'required|trim|integer|in_list[0,1]',
            ),
            array(
                'field' => 'account_info',
                'label' => '开户人名称',
                'rules' => 'trim|required|integer|in_list[0,1]',
            ),
            array(
                'field' => 'account',
                'label' => '开户人名称',
                'rules' => 'trim',
            ),
            array(
                'field' => 'bank_name',
                'label' => '开户行',
                'rules' => 'trim',
            ),
            array(
                'field' => 'bank_card_number',
                'label' => '银行卡号',
                'rules' => 'trim',
            ),
            array(
                'field' => 'bank_card_front_img',
                'label' => '银行卡正面照',
                'rules' => 'trim',
            ),
            array(
                'field' => 'bank_card_back_img',
                'label' => '银行卡反面照',
                'rules' => 'trim',
            ),
            array(
                'field' => 'card_front_img',
                'label' => '身份证正面照',
                'rules' => 'trim',
            ),
            array(
                'field' => 'card_back_img',
                'label' => '身份证反面照',
                'rules' => 'trim',
            ),
        );
    }

    /**
     * 检查免责退的资格
     */
    private function checkType($type,$resident)
    {
        if($type!='NO_LIABILITY'){
            return true;
        }else{
            $begin_time = Carbon::parse($resident->begin_time);
            if(Carbon::now()->diffInDays($begin_time,false)>3){
                return false;
            }
            return true;
        }
    }





    /**
     * 首次点击下一步计算金额展示给前端的验证规则
     */
    private function validateInitMoneyRequest()
    {
        return array(
            array(
                'field' => 'room_id',
                'label' => '房间信息',
                'rules' => 'required|trim|numeric',
            ),
            array(
                'field' => 'resident_id',
                'label' => '住户信息',
                'rules' => 'required|trim|numeric',
            ),
            array(
                'field' => 'type',
                'label' => '退租类型',
                'rules' => 'required|trim|in_list[NORMAL_REFUND,UNDER_CONTRACT,NO_LIABILITY]',
            ),
            array(
                'field' => 'refund_time_e',
                'label' => '退租日期',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'reason_e',
                'label' => '退租原因',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'remark_e',
                'label' => '备注',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'utility[]',
                'label' => '只能水电表信息',
                'rules' => 'trim',
            ),
            array(
                'field' => 'check_images[]',
                'label' => '验房图片',
                'rules' => 'trim|required',
            ),
        );
    }

    /**
     * 验证退房房间
     */
    public function checkRoom($room,$resident,$orders){
        //检查房间及房间状态
        if (empty($room)) {
            return 1007;
        }
        if ($room->status!=Roomunionmodel::STATE_RENT) {
            return 10034;
        }
        //检查住户及住户状态
        if (empty($resident)) {
            return 10011;
        }
        if ($resident->status!=Residentmodel::STATE_NORMAL) {
            return 10011;
        }
        //检查住户账单状态
        $confirmOrders  = $orders->where('status',Ordermodel::STATE_CONFIRM)->count();
        if($confirmOrders){
            return 10040;
        }
        $generateOrders = $orders->where('status',Ordermodel::STATE_GENERATED)->sum('money');
        if ($generateOrders>0) {
            return 10041;
        }
        return 0;
    }

    /**
     * 保存验房图片
     */
    private function storeCheckRoomImage($checkout,$images)
    {
        $this->load->model('checkoutimagemodel');
        $res    = Checkoutimagemodel::store($checkout,$this->splitAliossUrl($images,true));
        return $res;
    }

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
        $roomunion      = Roomunionmodel::where('id',$post['room_id'])->first(['building_id','store_id','cold_water_price','electricity_price','hot_water_price']);
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
        $money = [];
        //上传冷水表读数
        if (!empty($post['coldwater_reading'])){
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
                'image'         => empty($post['coldwater_image'])?'':$this->splitAliossUrl($post['coldwater_image']),
                'this_time'     => $post['coldwater_time'],
                'status'        => Meterreadingtransfermodel::REFUND,
            ];
            $coldwater->fill($arr_coldwater);
            if ($coldwater->save() && isset($last_coldwater->this_reading)) {
                $money['water'] = (floatval($post['coldwater_reading']) - $last_coldwater->this_reading) * $roomunion->cold_water_price;
                if (0.01 > $money['water']) {
                    return null;
                }
            }
        }
        //上传电表读数
        if (!empty($post['electric_reading'])){
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
                'image'         => empty($post['electric_image'])?'':$this->splitAliossUrl($post['electric_image']),
                'this_time'     => $post['electric_time'],
                'status'        => Meterreadingtransfermodel::REFUND,
            ];
            $electric->fill($arr_electric);
            if($electric->save()&&isset($last_electric->this_reading)){
                $money['electric']      = (floatval($post['electric_reading']) - $last_electric->this_reading) * $roomunion->electricity_price;
                if (0.01 > $money['electric']) {
                    return null;
                }
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
                'this_time'     => $post['hotwater_time'],
                'status'        => Meterreadingtransfermodel::REFUND,
            ];
            $hotwater->fill($arr_hotwater);
            if($hotwater->save()&&isset($last_hotwater->this_reading)){
                $money['hot_water']     = (floatval($post['hotwater_reading']) - $last_hotwater->this_reading) * $roomunion->hot_water_price;
                if (0.01 > $money['hot_water']) {
                    return null;
                }
            }
        }
        return $money;
    }

}
