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
            'coldwater_reading','hotwater_reading','electric_reading',
            'coldwater_image','hotwater_image','electric_image','check_images'
        ];
        if (!$this->validationText($this->validateInitMoneyRequest())){
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
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
        $handle_time    = Carbon::now();
        $refundMoney    = $this->calcInitRefundMoney($input,$room,$resident,$orders,$handle_time);
        $charge_order   = $refundMoney['charge_order'];
        $spend_order    = $refundMoney['spend_order'];
        $refund_sum     = $refundMoney['refund_sum'];
        $charge_sum     = $refundMoney['charge_sum'];
        $spend_sum      = $refundMoney['spend_sum'];

        $this->api_res(0,[
            'charge_order'=>$charge_order,
            'spend_order'=>$spend_order,
            'refund_sum'=>number_format($refund_sum,2,'.',''),
            'charge_sum'=>number_format($charge_sum,2,'.',''),
            'spend_sum'=>number_format($spend_sum,2,'.','')]);
    }

    /**
     * 退房-确认验房（确认）
     */
    public function confirm()
    {
        $field  = [
            'room_id','resident_id',
            'type','refund_time_e','reason_e','remark_e',
            'coldwater_reading','hotwater_reading','electric_reading',
            'coldwater_image','hotwater_image','electric_image','check_images',
            'create_orders','give_up','account_info',
            'account','bank_name','bank_card_number','bank_card_front_img','bank_card_back_img',
            'card_front_img','card_back_img',
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
        $refundMoney    = $this->calcInitRefundMoney($input,$room,$resident,$orders,$handle_time);
        //计算退房添加的账单
        $create_orders  = $this->calcConfirmOrder($input['create_orders'],$room,$resident,$handle_time);
        //merge
        $charge_order   = array_merge($refundMoney['charge'],$create_orders);
        $spend_order    = $refundMoney['spend'];
        $charge_sum     = 0;
        foreach ($charge_order as $item) {
            $charge_sum += $item['money'];
        }
        $spend_sum    = 0;
        foreach ($spend_order as $item) {
            $spend_sum += $item['money'];
        }
        $refund_sum = $spend_sum-$charge_sum;
        try {
            $this->load->model('checkoutrecordmodel');
            DB::beginTransaction();
            //生成退租单
            $record = $this->createCheckoutRecord($input);
            // 把水电读数存入记录表
            $utility_readings   = $this->utilityRecord($input);
            if (!empty($utility_readings)) {
               $record->utility_readings    = json_encode($utility_readings);
               $record->save();
            }
            if (!empty($create_orders)) {
                $record->add_orders = json_encode($create_orders);
                $record->save();
            }
            $this->load->model('taskflowmodel');
            $this->load->model('storemodel');
            $msg    = [
                'store_name'=> Storemodel::find($room->store_id)->name,
                'number'    => $room->number,
                'name'      => $resident->name,
                'create_name'   => $this->employee->name,
                'phone'     => $resident->phone,
            ];
            //生成退款任务流
            $taskflow_id  = $this->createTaskflow($record,$input['type'],$input['give_up'],$refund_sum,$msg);
            if (!empty($taskflow_id)) {
                $record->taskflow_id = $taskflow_id;
                $record->status = 'AUDIT';
                $record->save();
            } else {
                //如果没有任务流就直接根据记录生成账单
                $this->handleCheckoutOrder($record);
            }
            //重置房间状态
            $room->update(
                [
                    'status'        =>Roomunionmodel::STATE_BLANK,
                    'resident_id'   => 0,
                    'people_count'  => 0,
                    ]
            );
            //更新住户状态
            Residentmodel::where('id', $input['resident_id'])->update(['status' => 'CHECKOUT']);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
        $this->api_res(0);
    }

    /**
     * 处理退房的账单
     */
    private function handleCheckoutOrder($record)
    {
        $resident   = Residentmodel::find($record->resident_id);
        $room       = Roomunionmodel::find($record->room_id);
        $orders     = $resident->orders;
        //办理时间
        $handle_time    = $record->handle_time;
        $utility_readings   = $record->utility_readings;
        //生成水电账单
        if( !empty($utility_readings) ) {
            $utility_readings   = json_decode($utility_readings,true);
            $param_utility  = [];
            foreach ($utility_readings as $utility_reading) {
                $param_utility['room_id']   = $room->id;
                $param_utility['$resident'] = $resident->id;
                switch ($utility_reading['type']) {
                    case 'COLDWATER':
                        $param_utility['coldwater_reading'] = $utility_reading['this_reading'];
                        $param_utility['coldwater_image'] = $utility_reading['image'];
                        $param_utility['coldwater_time'] = $utility_reading['time'];
                        break;
                    case 'HOTWATER':
                        $param_utility['hotwater_reading'] = $utility_reading['this_reading'];
                        $param_utility['hotwater_image'] = $utility_reading['image'];
                        $param_utility['hotwater_time'] = $utility_reading['time'];
                        break;
                    case 'ELECTRIC':
                        $param_utility['electric_reading'] = $utility_reading['this_reading'];
                        $param_utility['electric_image'] = $utility_reading['image'];
                        $param_utility['electric_time'] = $utility_reading['time'];
                        break;
//                    case 'GAS':
//                        break;
                }
            }
            $utlity = $this->utility($param_utility);
            $bills  = [];
            if (isset($utlity['water'])){
                $bills['WATER'] = $utlity['water'];
            }
            if (isset($utlity['hot_water'])){
                $bills['HOT_WATER'] = $utlity['hot_water'];
            }
            if (isset($utlity['electric'])){
                $bills['ELECTRICITY'] = $utlity['electric'];
            }
            $utility_orders = $this->createCheckoutUtilityOrder($record,$room,$resident,$bills);

            Ordermodel::insert($utility_orders);
        }
        //生成添加的账单
        if ( !empty($record->add_orders) ) {
            $add_orders = json_decode($record->add_orders);
            Ordermodel::insert($add_orders);
        }
        //处理退房账单
        $this->handleInitRefundMoney($record,$room,$resident,$orders,$handle_time);

    }

    /**
     * 处理退房的初始账单
     */
    private function handleInitRefundMoney($record,$room,$resident,$orders,$handle_time)
    {
        $type   = $record->type;
        //处理应交
        $this->handleChargeMoney($type,$room,$resident,$orders,$handle_time);
        //处理已交
        $this->handleSpendMoney($type,$room,$resident,$orders,$handle_time);
    }

    /**
     * 处理应缴账单
     */
    private function handleChargeMoney($type,$room,$resident,$orders,$handle_time)
    {
        switch ($type) {
            case 'NORMAL_REFUND':
                $chargeOrders   = $this->handleChargeNormalMoney($room,$resident,$orders,$handle_time);
                break;
            case 'UNDER_CONTRACT':
                $chargeOrders   = $this->handleChargeUnderMoney($room,$resident,$orders,$handle_time);
                break;
            case 'NO_LIABILITY':
                $chargeOrders   = $this->handleChargeNoMoney($room,$resident,$orders,$handle_time);
                break;
            default:
                $chargeOrders   = false;
                break;
        }
    }

    private function handleChargeNoMoney($room,$resident,$orders,$handle_time)
    {
        $orders = $this->calcChargeNoMoney($room,$resident,$orders,$handle_time);
        Ordermodel::insert($orders);
    }

    /**
     * 处理违约退房的账单
     */
    private function handleChargeUnderMoney($room,$resident,$orders,$handle_time)
    {
        $now    = $handle_time;
        $order_end_time = $now->copy()->endOfMonth();
        //补到当月月底的账单
        $fillOrders = $this->fillOrder($resident,$room,$order_end_time,$orders);

        $reOrders   = $orders->map(function($order){
            $time   = $order->year.'-'.$order->month;
            $order->merge_time  = $time;
            return $order;
        });
        //超出当月的账单（未支付的，关闭掉）
        $beyondOrders   = $reOrders->where('merge_time','>',$now->format('Y-m'))->where('status','PENDING')
            ->each(function($order){
            $order->update(['status'=>'CLOSE','tag'=>'CHECKOUT']);
        });
        //当月以及当月之前的未支付账单
        $pendingOrders  = $reOrders->where('merge_time','<=',$now->format('Y-m'))->where('status','PENDING')
            ->each(function($order){
                $order->update(['tag'=>'CHECKOUT']);
            });
        //生成当月违约金账单
        $underOrders = [
            [
                'number'=>Ordermodel::newNumber(),
                'store_id'  => $resident->store_id,
                'company_id'=> $resident->company_id,
                'room_id'   => $resident->room_id,
                'customer_id'   => $resident->customer_id,
                'uxid'      => $resident->uxid,
                'employee_id'   => $this->employee->id,
                'room_type_id'  => $room->room_type_id,
                'money'     => $resident->real_rent_money,
                'paid'      => $resident->real_rent_money,
                'type'      => 'BREAK',
                'year'      => $now->year,
                'month'     => $now->month,
                'status'    => 'PENDING',
                'pay_status'=> 'RENEWALS',
                'begin_time'=> $handle_time->copy()->startOfMonth()->format('Y-m-d'),
                'end_time'  => $handle_time->copy()->endOfMonth()->format('Y-m-d'),
                'tag'       => 'CHECKOUT',
            ]
        ];
        Ordermodel::insert($fillOrders);
        Ordermodel::insert($underOrders);
        return true;
    }

    /**
     * 处理正常退房时应缴账单
     */
    private function handleChargeNormalMoney($room,$resident,$orders,$handle_time)
    {
        //检查住户合同期内的账单是否已经全部生成
        $end_time   = $handle_time;
        //补充应该生成的账单
        $fillOrders = $this->fillOrder($resident,$room,$end_time,$orders);
        $pendingOrders  = $orders->where('status','PENDING')->each(function($order){
            $order->update(['tag'=>'CHECKOUT' ]);
        });
        Ordermodel::insert($fillOrders);
        return true;
    }

    /**
     * 处理已交账单
     */
    private function handleSpendMoney($type,$room,$resident,$orders,$handle_time)
    {
        switch ($type) {
            case 'NORMAL_REFUND':
                $spendOrders    = $this->handleSpendNormalMoney($room,$resident,$orders,$handle_time);
                break;
            case 'UNDER_CONTRACT':
                $spendOrders    = $this->handleSpendUnderMoney($orders,$handle_time);
                break;
            case 'NO_LIABILITY':
                $spendOrders    = $this->handleSpendNoMoney($orders,$handle_time);
                break;
            default:
                $spendOrders    = [];
                break;
        }
        return $spendOrders;
    }

    /**
     * 处理免责退房时的已交账单
     */
    private function handleSpendNoMoney($orders,$handle_time)
    {
        $spendOrders    = $orders->whereIn('status',['CONFIRM','COMPLATE'])
        ->each(function($order){
            $order->update(['tag'=>'CHECKOUT']);
        });
        return true;
    }

    /**
     * 处理正常退房时的已交账单
     */
    private function handleSpendNormalMoney($room,$resident,$orders,$handle_time)
    {
        $deposit    = $orders->whereIn('type',['DEPOSIT_R','DEPOSIT_O'])
        ->each(function($order){
            $order->update(['tag'=>'CHECKOUT']);
        });
        return true;
    }

    /**
     * 处理违约退房已交账单
     */
    private function handleSpendUnderMoney($orders,$handle_time)
    {
        $deposit    = $orders->whereIn('type',['DEPOSIT_R','DEPOSIT_O'])
            ->each(function($order){
                $order->update(['tag'=>'CHECKOUT']);
            });
        $reOrders   = $orders->map(function($order){
            $time   = $order->year.'-'.$order->month;
            $order->merge_time  = $time;
            return $order;
        });
        //超出当月的已支付账单
        $beyondOrders   = $reOrders->where('merge_time','>',$handle_time->format('Y-m'))
            ->whereIn('status',['COMPLATE','COMFIRM'])
            ->each(function($order){
                $order->update(['tag'=>'CHECKOUT']);
            });
        return true;
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
            $taskflow_id   = $this->taskflowmodel->createTaskflow($this->company_id,Taskflowmodel::TYPE_GIVE_UP,$this->employee->store_id,$checkout->room_id,Taskflowmodel::CREATE_EMPLOYEE,$this->employee->id,null,null,$msg);
        } else {
            if ($type=='NORMAL_REFUND') {
                //正常退房走正常退房的任务流
                $msg['type']='正常';
                $msg = json_encode($msg);
                $taskflow_id   = $this->taskflowmodel->createTaskflow($this->company_id,Taskflowmodel::TYPE_CHECKOUT,$this->employee->store_id,$checkout->room_id,Taskflowmodel::CREATE_EMPLOYEE,$this->employee->id,null,null,$msg);
            } elseif ($type=='NO_LIABILITY') {
                //三天免责走三天免责的任务流
                $msg['type']='免责';
                $msg = json_encode($msg);
                $taskflow_id   = $this->taskflowmodel->createTaskflow($this->company_id,Taskflowmodel::TYPE_CHECKOUT_NO_LIABILITY,$this->employee->store_id,$checkout->room_id,Taskflowmodel::CREATE_EMPLOYEE,$this->employee->id,null,null,$msg);
            } elseif ($type=='UNDER_CONTRACT') {
                if($refund_money>0){
                    //违约退房退款大于0
                    $msg['type']='违约退款金额小于0';
                    $msg = json_encode($msg);
                    $taskflow_id   = $this->taskflowmodel->createTaskflow($this->company_id,Taskflowmodel::TYPE_CHECKOUT_UNDER_CONTRACT,$this->employee->store_id,$checkout->room_id,Taskflowmodel::CREATE_EMPLOYEE,$this->employee->id,null,null,$msg);
                }else{
                    //违约退房退款小于0
                    $msg['type']='违约退款金额大于0';
                    $msg = json_encode($msg);
                    $taskflow_id   = $this->taskflowmodel->createTaskflow($this->company_id,Taskflowmodel::TYPE_CHECKOUT_UNDER_CONTRACT_LESS,$this->employee->store_id,$checkout->room_id,Taskflowmodel::CREATE_EMPLOYEE,$this->employee->id,null,null,$msg);

                }
            }else{
                $taskflow_id = null;
            }
        }
        return $taskflow_id;
    }

    /**
     * @param $input
     * @param $room
     * @param $resident
     * @param $orders
     * @return array
     * 根据上传的类型已经水电读数计算一个初始的退款
     */
    private function calcInitRefundMoney($input,$room,$resident,$orders,$handle_time)
    {
        // 按读数计算应收的水电费账单
        $utility_order  = $this->calcChargeUtilityMoney($input,$handle_time);
        //计算一下初始的金额返回给前端
        $init_order     = $this->calcInitMoney($input['type'],$room,$resident,$orders,$handle_time);
        //merge
        $charge_order   = array_merge($utility_order,$init_order['charge']);
        $spend_order    = $init_order['spend'];
        $charge_sum     = 0;
        foreach ($charge_order as $item) {
            $charge_sum += $item['money'];
        }
        $spend_sum    = 0;
        foreach ($spend_order as $item) {
            $spend_sum += $item['money'];
        }
        $refund_sum = $spend_sum-$charge_sum;
        return compact('charge_order','spend_order','charge_sum','spend_sum','refund_sum');
    }

    /**
     * 生成退房时的水电读数记录
     */
    private function utilityRecord($input){
        $arr    = [];
        if (!empty($input['coldwater_reading'])) {
            $arr[]  = [
                'type' => 'COLDWATER',
                'this_reading'  => $input['coldwater_reading'],
                'time'      => date('Y-m-d H:i:s',time()),
                'image'     => $input['coldwater_image']
            ];
        }
        if (!empty($input['hotwater_reading'])) {
            $arr[]  = [
                'type' => 'HOTWATER',
                'this_reading'  => $input['hotwater_reading'],
                'time'      => date('Y-m-d H:i:s',time()),
                'image'     => $input['hotwater_image']
            ];
        }
        if (!empty($input['electric_reading'])) {
            $arr[]  = [
                'type' => 'ELECTRIC',
                'this_reading'  => $input['electric_reading'],
                'time'      => date('Y-m-d H:i:s',time()),
                'image'     => $input['electric_image']
            ];
        }
        return $arr;
    }

    /**
     * 生成退房单
     */
    private function createCheckoutRecord($input)
    {
        $record = new Checkoutmodel();
        $record->store_id   = $this->employee->store_id;
        $record->room_id    = $this->$input['room_id'];
        $record->resident_id= $this->$input['resident_id'];
        $record->employee_id= $this->employee->id;
        $record->status     = Checkoutmodel::STATUS_UNPAID;
        $record->type       = $input['type'];
        $record->refund_time_e  = $input['refund_time_e'];
        $record->reason_e   = $input['reason_e'];
        $record->remark_e   = $input['remark_e'];
        $record->give_up    = $input['give_up'];
        $record->handle_time= Carbon::now();
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
     * 确认退房时的账单处理
     */
    private function calcConfirmOrder($create_orders,$room,$resident,$handle_time)
    {
        $orders = [];
        $now    = $handle_time;
        if (!empty($create_orders)) {
            foreach ($create_orders as $create_order){
                $order  = [
                    'number'=>Ordermodel::newNumber(),
                    'store_id'  => $resident->store_id,
                    'company_id'=> $resident->company_id,
                    'room_id'   => $resident->room_id,
                    'customer_id'   => $resident->customer_id,
                    'uxid'      => $resident->uxid,
                    'employee_id'   => $this->employee->id,
                    'room_type_id'  => $room->room_type_id,
                    'money'     => $resident->real_rent_money,
                    'paid'      => $resident->real_rent_money,
                    'type'      => $create_order['type'] ,
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
        }
        return $orders;
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
                'rules' => 'required|trim',
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
     * 按读数计算应收的水电费账单
     */
    private function calcChargeUtilityMoney($input,$handle_time)
    {
        return [];
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
     * 计算初始金额
     */
    private function calcInitMoney($type,$room,$resident,$orders,$handle_time)
    {
        //计算应缴
        $chargeOrders   = $this->calcChargeMoney($type,$room,$resident,$orders,$handle_time);
        //计算已交
        $spendOrders    = $this->calcSpendMoney($type,$room,$resident,$orders,$handle_time);
        return ['charge'=>$chargeOrders,'spend'=>$spendOrders];

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
                'field' => 'coldwater_reading',
                'label' => '冷水读数',
                'rules' => 'trim',
            ),
            array(
                'field' => 'hotwater_reading',
                'label' => '热水读数',
                'rules' => 'trim',
            ),
            array(
                'field' => 'electric_reading',
                'label' => '电表读数',
                'rules' => 'trim',
            ),
            array(
                'field' => 'coldwater_image',
                'label' => '冷水图片',
                'rules' => 'trim',
            ),
            array(
                'field' => 'hotwater_image',
                'label' => '热水图片',
                'rules' => 'trim',
            ),
            array(
                'field' => 'electric_image',
                'label' => '电表读数',
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
     * 计算退房时应收的金额
     */
    private function calcChargeMoney($type,$room,$resident,$orders,$handle_time)
    {
        switch ($type) {
            case 'NORMAL_REFUND':
                $chargeOrders   = $this->calcChargeNormalMoney($room,$resident,$orders,$handle_time);
                break;
            case 'UNDER_CONTRACT':
                $chargeOrders   = $this->calcChargeUnderMoney($room,$resident,$orders,$handle_time);
                break;
            case 'NO_LIABILITY':
                $chargeOrders   = $this->calcChargeNoMoney($room,$resident,$orders,$handle_time);
                break;
            default:
                $chargeOrders   = [];
                break;
        }
        return $chargeOrders;
    }

    /**
     * 计算违约退房应收金额
     */
    private function calcChargeUnderMoney($room,$resident,$orders,$handle_time)
    {
        $now    = $handle_time;
        $order_end_time = $now->copy()->endOfMonth();
        //补到当月月底的账单
        $fillOrders = $this->fillOrder($resident,$room,$order_end_time,$orders);

        $reOrders   = $orders->map(function($order){
            $time   = $order->year.'-'.$order->month;
            $order->merge_time  = $time;
            return $order;
        });
        //超出当月的账单（未支付的，后面需要处理）
        $beyondOrders   = $reOrders->where('merge_time','>',$now->format('Y-m'))->where('status','PENDING');
        //当月以及当月之前的未支付账单
        $pendingOrders  = $reOrders->where('merge_time','<=',$now->format('Y-m'))->where('status','PENDING');
        //生成当月违约金账单
        $underOrders = [
            [
                'number'=>Ordermodel::newNumber(),
                'store_id'  => $resident->store_id,
                'company_id'=> $resident->company_id,
                'room_id'   => $resident->room_id,
                'customer_id'   => $resident->customer_id,
                'uxid'      => $resident->uxid,
                'employee_id'   => $this->employee->id,
                'room_type_id'  => $room->room_type_id,
                'money'     => $resident->real_rent_money,
                'paid'      => $resident->real_rent_money,
                'type'      => 'BREAK',
                'year'      => $now->year,
                'month'     => $now->month,
                'status'    => 'PENDING',
                'pay_status'=> 'RENEWALS',
                'begin_time'=> $handle_time->copy()->startOfMonth()->format('Y-m-d'),
                'end_time'  => $handle_time->copy()->endOfMonth()->format('Y-m-d'),
            ]
        ];
        $chargeOrders   = array_merge($pendingOrders,$underOrders,$fillOrders);
        return $chargeOrders;
    }

    /**
     * 计算免责退房应收金额
     */
    private function calcChargeNoMoney($room,$resident,$orders,$handle_time)
    {
        //退租时间就是当前时间
        $end_time   = $handle_time;
        $begin_time = Carbon::parse($resident->begin_time);
        $chargeOrders    = [];
        if ($begin_time->month==$end_time->month) {
            $year   = $end_time->year;
            $month  = $end_time->month;
            $endDate     = $end_time;
            $startDay    = $resident->begin_time->lte($endDate->copy()->startOfMonth()) ? 1 : $resident->begin_time->day;
            $daysOfMonth = $endDate->copy()->endOfMonth()->day;
            $rent_money  = ceil($resident->real_rent_money * ($endDate->day - $startDay + 1) / $daysOfMonth);
            $property    = ceil($resident->real_property_costs * ($endDate->day - $startDay + 1) / $daysOfMonth);
            //@1 先生成房租账单
            $rent_order  = [
                'number'    => Ordermodel::newNumber(),
                'store_id'  => $resident->store_id,
                'company_id'=> $resident->company_id,
                'room_id'   => $resident->room_id,
                'customer_id'   => $resident->customer_id,
                'uxid'      => $resident->uxid,
                'employee_id'   => $this->employee->id,
                'room_type_id'  => $room->room_type_id,
                'money'     => $rent_money,
                'paid'      => $rent_money,
                'type'      => 'ROOM',
                'year'      => $year,
                'month'     => $month,
                'status'    => 'PENDING',
                'pay_status'=> 'RENEWALS',
                'begin_time'=> $begin_time->format('Y-m-d'),
                'end_time'  => $endDate->format('Y-m-d'),
                'tag'       => 'CHECKOUT',
            ];
            //@2再生成物业账单
            $management_order   = [
                'number'    => Ordermodel::newNumber(),
                'store_id'  => $resident->store_id,
                'company_id'=> $resident->company_id,
                'room_id'   => $resident->room_id,
                'customer_id'   => $resident->customer_id,
                'uxid'      => $resident->uxid,
                'employee_id'   => $this->employee->id,
                'room_type_id'  => $room->room_type_id,
                'money'     => $property,
                'paid'      => $property,
                'type'      => 'MANAGEMENT',
                'year'      => $year,
                'month'     => $month,
                'status'    => 'PENDING',
                'pay_status'=> 'RENEWALS',
                'begin_time'=> $begin_time->format('Y-m-d'),
                'end_time'  => $endDate->format('Y-m-d'),
                'tag'       => 'CHECKOUT',
            ];
            $chargeOrders[] = $rent_order;
            $chargeOrders[] = $management_order;
        } else {
            //先生成合同开始当月的账单，
            $year1  = $begin_time->year;
            $month1 = $begin_time->month;
            $rentDays1  = $begin_time->diffInDays($begin_time->copy()->endOfMonth());
            $allDays1   = $begin_time->copy()->endOfMonth()->day;
            $rentMoney1 = ceil($resident->real_rent_money* $rentDays1 / $allDays1);
            $propertyMoney1 = ceil($resident->real_property_costs* $rentDays1 / $allDays1);
            //@1 先生成房租账单
            $rent_order1  = [
                'number'    => Ordermodel::newNumber(),
                'store_id'  => $resident->store_id,
                'company_id'=> $resident->company_id,
                'room_id'   => $resident->room_id,
                'customer_id'   => $resident->customer_id,
                'uxid'      => $resident->uxid,
                'employee_id'   => $this->employee->id,
                'room_type_id'  => $room->room_type_id,
                'money'     => $rentMoney1,
                'paid'      => $rentMoney1,
                'type'      => 'ROOM',
                'year'      => $year1,
                'month'     => $month1,
                'status'    => 'PENDING',
                'pay_status'=> 'RENEWALS',
                'begin_time'=> $begin_time->format('Y-m-d'),
                'end_time'  => $begin_time->copy()->endOfMonth()->format('Y-m-d'),
                'tag'       => 'CHECKOUT',
            ];
            //@2再生成物业账单
            $management_order1   = [
                'number'    => Ordermodel::newNumber(),
                'store_id'  => $resident->store_id,
                'company_id'=> $resident->company_id,
                'room_id'   => $resident->room_id,
                'customer_id'   => $resident->customer_id,
                'uxid'      => $resident->uxid,
                'employee_id'   => $this->employee->id,
                'room_type_id'  => $room->room_type_id,
                'money'     => $propertyMoney1,
                'paid'      => $propertyMoney1,
                'type'      => 'MANAGEMENT',
                'year'      => $year1,
                'month'     => $month1,
                'status'    => 'PENDING',
                'pay_status'=> 'RENEWALS',
                'begin_time'=> $begin_time->format('Y-m-d'),
                'end_time'  => $begin_time->copy()->endOfMonth()->format('Y-m-d'),
                'tag'       => 'CHECKOUT',
            ];
            //再生成退房当月的账单
            $year2  = $end_time->year;
            $month2 = $end_time->month;
            $rentDays2  = $end_time->diffInDays($end_time->copy()->startOfMonth());
            $allDays2   = $end_time->copy()->startOfMonth()->day;
            $rentMoney2 = ceil($resident->real_rent_money* $rentDays2 / $allDays2);
            $propertyMoney2 = ceil($resident->real_property_costs* $rentDays2 / $allDays2);
            $rent_order2  = [
                'number'    => Ordermodel::newNumber(),
                'store_id'  => $resident->store_id,
                'company_id'=> $resident->company_id,
                'room_id'   => $resident->room_id,
                'customer_id'   => $resident->customer_id,
                'uxid'      => $resident->uxid,
                'employee_id'   => $this->employee->id,
                'room_type_id'  => $room->room_type_id,
                'money'     => $rentMoney2,
                'paid'      => $rentMoney2,
                'type'      => 'ROOM',
                'year'      => $year2,
                'month'     => $month2,
                'status'    => 'PENDING',
                'pay_status'=> 'RENEWALS',
                'begin_time'=> $end_time->copy()->startOfMonth()->format('Y-m-d'),
                'end_time'  => $end_time->format('Y-m-d'),
                'tag'       => 'CHECKOUT',
            ];
            //@2再生成物业账单
            $management_order2   = [
                'number'    => Ordermodel::newNumber(),
                'store_id'  => $resident->store_id,
                'company_id'=> $resident->company_id,
                'room_id'   => $resident->room_id,
                'customer_id'   => $resident->customer_id,
                'uxid'      => $resident->uxid,
                'employee_id'   => $this->employee->id,
                'room_type_id'  => $room->room_type_id,
                'money'     => $propertyMoney2,
                'paid'      => $propertyMoney2,
                'type'      => 'MANAGEMENT',
                'year'      => $year2,
                'month'     => $month2,
                'status'    => 'PENDING',
                'pay_status'=> 'RENEWALS',
                'begin_time'=> $end_time->copy()->startOfMonth()->format('Y-m-d'),
                'end_time'  => $end_time->format('Y-m-d'),
                'tag'       => 'CHECKOUT',
            ];
            $chargeOrders[] = $rent_order1;
            $chargeOrders[] = $management_order1;
            $chargeOrders[] = $rent_order2;
            $chargeOrders[] = $management_order2;
        }
        return $chargeOrders;
    }

    /**
     * 正常退房时应收金额
     */
    private function calcChargeNormalMoney($room,$resident,$orders,$handle_time)
    {
        //检查住户合同期内的账单是否已经全部生成
        $end_time   = $resident->end_time;
        //补充应该生成的账单
        $fillOrders = $this->fillOrder($resident,$room,$end_time,$orders);
        $pendingOrders  = $orders->where('status','PENDING')->toArray();
        $chargeOrder    = array_merge($fillOrders,$pendingOrders);
        return $chargeOrder;
    }

    /**
     * 补充某个日期内未生成的账单信息，返回array
     */
    private function fillOrder($resident,$room,Carbon $time,$orders)
    {
        //查看有效账单
        $orders_time  = $orders->whereIn('status',['COMPLATE','CONFIRM','PENDING','GENERATE'])->map(function($order){
            $time   = $order->year.'-'.$order->month;
            return $time;
        })->toArray();
        //已生成的最大的账单周期
        $max_order_time = Carbon::parse(max($orders_time));
        //比较最新账单和time日期的月份
        $diff_month   = $time->diffInMonths($max_order_time,false);
        if ($diff_month<=0) {
            //如果最新账单就是time当月就是没有需要生成的账单，则返回[]
            $fill_orders    =  [];
        } else {
            //否则就从最新日期月的下一个开始生成账单，直到日期日
            $fill_orders  = [];
            for ($i=$diff_month;$i>0;$i--) {
                $current_time   = $max_order_time->addMonth(1);
                $year   = $current_time->year;
                $month  = $current_time->month;
                if ($i==1) {
                    //如果是日期的最后一个月按天数生成账单
                    //当月的房租计算开始日期, 一般应该是从1号开始计算, 但是万一有入住当月就退房的情况呢?
                    $endDate     = $time;
                    $startDay    = $resident->begin_time->lte($endDate->copy()->startOfMonth()) ? 1 : $resident->begin_time->day;
                    $daysOfMonth = $endDate->copy()->endOfMonth()->day;
                    $rent_money  = ceil($resident->real_rent_money * ($endDate->day - $startDay + 1) / $daysOfMonth);
                    $property    = ceil($resident->real_property_costs * ($endDate->day - $startDay + 1) / $daysOfMonth);
                    //@1 先生成房租账单
                    $rent_order  = [
                        'number'    => Ordermodel::newNumber(),
                        'store_id'  => $resident->store_id,
                        'company_id'=> $resident->company_id,
                        'room_id'   => $resident->room_id,
                        'customer_id'   => $resident->customer_id,
                        'uxid'      => $resident->uxid,
                        'employee_id'   => $this->employee->id,
                        'room_type_id'  => $room->room_type_id,
                        'money'     => $rent_money,
                        'paid'      => $rent_money,
                        'type'      => 'ROOM',
                        'year'      => $year,
                        'month'     => $month,
                        'status'    => 'PENDING',
                        'pay_status'=> 'RENEWALS',
                        'begin_time'=> $current_time->copy()->startOfMonth()->format('Y-m-d'),
                        'end_time'  => $endDate->format('Y-m-d'),
                        'tag'       => 'CHECKOUT',
                    ];
                    //@2再生成物业账单
                    $management_order   = [
                        'number'    => Ordermodel::newNumber(),
                        'store_id'  => $resident->store_id,
                        'company_id'=> $resident->company_id,
                        'room_id'   => $resident->room_id,
                        'customer_id'   => $resident->customer_id,
                        'uxid'      => $resident->uxid,
                        'employee_id'   => $this->employee->id,
                        'room_type_id'  => $room->room_type_id,
                        'money'     => $property,
                        'paid'      => $property,
                        'type'      => 'MANAGEMENT',
                        'year'      => $year,
                        'month'     => $month,
                        'status'    => 'PENDING',
                        'pay_status'=> 'RENEWALS',
                        'begin_time'=> $current_time->copy()->startOfMonth()->format('Y-m-d'),
                        'end_time'  => $endDate->format('Y-m-d'),
                        'tag'       => 'CHECKOUT',
                    ];
                    $fill_orders[]  = $rent_order;
                    $fill_orders[]  = $management_order;
                }else{
                    //如果不是最后一个月的，则生成整月账单
                    //@1 先生成房租账单
                    $rent_order  = [
                        'number'    => Ordermodel::newNumber(),
                        'store_id'  => $resident->store_id,
                        'company_id'=> $resident->company_id,
                        'room_id'   => $resident->room_id,
                        'customer_id'   => $resident->customer_id,
                        'uxid'      => $resident->uxid,
                        'employee_id'   => $this->employee->id,
                        'room_type_id'  => $room->room_type_id,
                        'money'     => $resident->real_rent_money,
                        'paid'      => $resident->real_rent_money,
                        'type'      => 'ROOM',
                        'year'      => $year,
                        'month'     => $month,
                        'status'    => 'PENDING',
                        'pay_status'=> 'RENEWALS',
                        'begin_time'=> $current_time->copy()->startOfMonth()->format('Y-m-d'),
                        'end_time'  => $current_time->copy()->endOfMonth()->format('Y-m-d'),
                        'tag'       => 'CHECKOUT',
                    ];
                    //@2再生成物业账单
                    $management_order   = [
                        'number'    => Ordermodel::newNumber(),
                        'store_id'  => $resident->store_id,
                        'company_id'=> $resident->company_id,
                        'room_id'   => $resident->room_id,
                        'customer_id'   => $resident->customer_id,
                        'uxid'      => $resident->uxid,
                        'employee_id'   => $this->employee->id,
                        'room_type_id'  => $room->room_type_id,
                        'money'     => $resident->real_property_money,
                        'paid'      => $resident->real_property_money,
                        'type'      => 'MANAGEMENT',
                        'year'      => $year,
                        'month'     => $month,
                        'status'    => 'PENDING',
                        'pay_status'=> 'RENEWALS',
                        'begin_time'=> $current_time->copy()->startOfMonth()->format('Y-m-d'),
                        'end_time'  => $current_time->copy()->endOfMonth()->format('Y-m-d'),
                        'tag'       => 'CHECKOUT',
                        ];
                    $fill_orders[]  = $rent_order;
                    $fill_orders[]  = $management_order;
                }
            }
        }
        return $fill_orders;
    }

    /**
     * 计算退房时应付的金额
     */
    private function calcSpendMoney($type,$room,$resident,$orders,$handle_time)
    {
        switch ($type) {
            case 'NORMAL_REFUND':
                $spendOrders    = $this->calcSpendNormalMoney($room,$resident,$orders,$handle_time);
                break;
            case 'UNDER_CONTRACT':
                $spendOrders    = $this->calcSpendUnderMoney($orders,$handle_time);
                break;
            case 'NO_LIABILITY':
                $spendOrders    = $this->calcSpendNoMoney($orders,$handle_time);
                break;
            default:
                $spendOrders    = [];
                break;
        }
        return $spendOrders;
    }

    /**
     * @param $orders
     * @return mixed
     * 违约退房已交账单
     */
    private function calcSpendUnderMoney($orders,$handle_time){
        $deposit    = $orders->whereIn('type',['DEPOSIT_R','DEPOSIT_O'])->toArray();
        $reOrders   = $orders->map(function($order){
            $time   = $order->year.'-'.$order->month;
            $order->merge_time  = $time;
            return $order;
        });
        //超出当月的已支付账单
        $beyondOrders   = $reOrders->where('merge_time','>',$handle_time->format('Y-m'))->where('status','COMPLATE')->toArray();
        $spendOrders    = array_merge($deposit,$beyondOrders);
        return $spendOrders;
    }

    /**
     * 计算三天免责应付账单
     */
    private function calcSpendNoMoney($orders,$handle_time){
        //已付金额是全部账单
        $spendOrders    = $orders->whereIn('status',['CONFIRM','COMPLATE'])->toArray();
        return $spendOrders;
    }

    /**
     * 计算正常退房时已交账单
     */
    private function calcSpendNormalMoney($room,$resident,$orders,$handle_time)
    {
        $deposit    = $orders->whereIn('type',['DEPOSIT_R','DEPOSIT_O'])->toArray();
        $spendOrder = $deposit;
        return $spendOrder;
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
        $res    = Checkoutimagemodel::store($checkout,$images);
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
