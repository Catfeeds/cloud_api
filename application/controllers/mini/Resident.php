<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Illuminate\Database\Capsule\Manager as DB;
use EasyWeChat\Foundation\Application;
use Carbon\Carbon;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/22 0022
 * Time:        9:23
 * Describe:    入住
 */
class Resident extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();

    }

    /**
     * 检查集中式房间号是否存在
     * 返回room_id
     */
    public function checkRoomUnion()
    {
        $store_id   = $this->employee->store_id;
        $room_number    = $this->input->post('room_number',true);
        $where      = ['store_id'=>$store_id,'number'=>$room_number];
        $this->load->model('roomunionmodel');
        if(!$room=Roomunionmodel::where($where)->first()){
            $this->api_res(1007);
            return;
        }
        $room_id    = $room->id;
        if(!$room->isBlank()){
            $this->api_res(10010);
            return;
        }
        $this->api_res(0,[
            'room_id'=>$room_id,
            'rent_price'=>$room->rent_price,
            'property_price'=>$room->property_price,
        ]);
    }

    /**
     * 办理入住
     * @param $store_id
     * @param $room_number
     */
    public function checkIn()
    {
        $field  = [
            'room_id','begin_time','people_count','contract_time','discount_id','first_pay_money',
            'deposit_money','deposit_month','tmp_deposit','rent_type','pay_frequency',
            'name','phone','card_type','card_number','card_one','card_two','card_three',
            'name_two','phone_two','card_type_two','card_number_two','alter_phone','alternative','address'
        ];
        if(!$this->validationText($this->validateCheckIn())){
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $this->load->model('residentmodel');
        $post   = $this->input->post(null,true);
        if(!$this->checkPhoneNumber($post['phone'])){
            $this->api_res(1002,['error'=>'请检查手机号']);
            return;
        }
        //var_dump($this->checkIdCardNumber($post['card_type'],$post['card_number']));exit;
        if(!$this->checkIdCardNumber($post['card_type'],$post['card_number'])){
            $this->api_res(1002,['error'=>'请检查身份证号']);
            return;
        }

        if(!empty($post['name_two'])){
            if(empty($post['phone_two']) || empty($post['card_type_two'] || empty($post['card_number_two']))){
                $this->api_res(1002,['error'=>'住户二信息不全']);
                return;

            }
            if(!$this->checkPhoneNumber($post['phone_two'])){
                $this->api_res(1002,['error'=>'请检查手机号']);
                return;
            }
            if(!$this->checkIdCardNumber($post['card_type_two'],$post['card_number_two'])){
                $this->api_res(1002,['error'=>'请检查身份证号']);
                return;
            }
        }
        //获取请求参数,
        $data   = $this->handleCheckInData($post);
        //var_dump($data);die();
        //获取房间信息
        $this->load->model('roomunionmodel');
        $room   = Roomunionmodel::find($post['room_id']);
        if(!$room){
            $this->api_res(1007);
            return;
        }
        if(!$room->isBlank()){
            $this->api_res(10010);
            return;
        }
        //创建住户
        $resident   = new Residentmodel();
        try{
            DB::beginTransaction();
            $resident->fill($data);

           // $resident->employee_id  = $this->employee->id;
            $resident->card_one = $this->splitAliossUrl($data['card_one']);
            $resident->card_two = $this->splitAliossUrl($data['card_two']);
            $resident->card_three = $this->splitAliossUrl($data['card_three']);
            $a=$resident->save();
            //ok
            $b=$this->handleCheckInCommonEvent($resident, $room);
            if($a && $b){
                DB::commit();
            }else{
                DB::rollBack();
                $this->api_res(1009);
                return;
            }
            $this->load->model('activitymodel');
            $this->load->model('coupontypemodel');
            $this->load->model('contractmodel');
            $data=$resident->transform($resident);
            //var_dump($data);
            $this->api_res(0,['data'=>$data]);
        }catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 处理办理入住时的住户信息
     */
    public function handleCheckInData($post)
    {
        $data   = $this->checkInRequestData($post);
        $data   = array_merge($data, [
            'status'        => Residentmodel::STATE_NOTPAY,
            'end_time'      => $this->residentmodel->contractEndDate($data['begin_time'], $data['contract_time']),
            'employee_id'   => $this->employee->id,
            'store_id'      => $this->employee->store_id,
            'data'          => json_encode(['card_img_path' => [
                                                'card_one'      => $this->splitAliossUrl($data['card_one']),
                                                'card_two'      => $this->splitAliossUrl($data['card_two']),
                                                'card_three'    => $this->splitAliossUrl($data['card_three']),
                                            ],
                ]),

        ]);
        return $data;
    }

    /**
     * 获取请求参数
     */
    private function checkInRequestData($post)
    {
        $input  = $post;
        $data   = [
            'name'                  => $input['name'],
            'phone'                 => $input['phone'],
            'card_number'           => $input['card_number'],
            'card_type'             => $input['card_type'],
            'address'               => $input['address'],
            'card_one'              => $input['card_one'],
            'card_two'              => $input['card_two'],
            'card_three'            => $input['card_three'],
            'alternative'           => $input['alternative'],
            'alter_phone'           => $input['alter_phone'],
            'room_id'               => $input['room_id'],
            'discount_id'           => $input['discount_id'],
            'pay_frequency'         => $input['pay_frequency'],
            'rent_type'             => $input['rent_type'],
            'contract_time'         => $input['contract_time'],
            'deposit_month'         => $input['deposit_month'],
            'tmp_deposit'           => $input['tmp_deposit'],
            'begin_time'            => Carbon::parse($input['begin_time']),
            'people_count'          => $input['people_count'],
            'first_pay_money'       => $input['first_pay_money'],
            'deposit_money'         => $input['deposit_money'],
            'real_rent_money'       => $input['real_rent_money'],
            'real_property_costs'   => $input['real_property_costs'],
        ];

        //随住人员的信息选填的
        if (!empty($post['name_two'])) {
            $data   = array_merge($data, [
                'name_two'          => $input['name_two'],
                'phone_two'         => $input['phone_two'],
                'card_type_two'     => $input['card_type_two'],
                'card_number_two'   => $input['card_number_two'],
            ]);
        }

        //备注信息也是选填的
        $data['remark']     = isset($input['remark']) ? $input['remark'] : '无';

        return $data;
    }

    /**
     * 办理入住验证
     */
    public function validateCheckIn()
    {
        return array(
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
                'field' => 'people_count',
                'label' => '入住人数',
                'rules' => 'required|trim|integer',
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
//            array(
//                'field' => 'real_rent_money',
//                'label' => '实际租金',
//                'rules' => 'trim|required|integer',
//            ),
//            array(
//                'field' => 'real_property_costs',
//                'label' => '实际物业费',
//                'rules' => 'trim|integer|required',
//            ),
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
                'field' => 'name',
                'label' => '住户名称',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'phone',
                'label' => '手机号',
                'rules' => 'required|trim|integer',
            ),
            array(
                'field' => 'card_type',
                'label' => '证件类型',
                'rules' => 'required|trim|in_list[0,1,2,6,A,B,C,E,F,P,BL]',
            ),
            array(
                'field' => 'card_number',
                'label' => '证件号码',
                'rules' => 'required|trim|integer',
            ),
            array(
                'field' => 'alternative',
                'label' => '紧急联系人',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'alter_phone',
                'label' => '联系方式',
                'rules' => 'required|trim|integer',
            ),
            array(
                'field' => 'address',
                'label' => '通讯地址',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'card_one',
                'label' => '证件照1',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'card_two',
                'label' => '证件照2',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'card_three',
                'label' => '证件照3',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'name_two',
                'label' => '住户名称',
                'rules' => 'trim',
            ),
            array(
                'field' => 'phone_two',
                'label' => '手机号',
                'rules' => 'trim|integer',
            ),
            array(
                'field' => 'card_type_two',
                'label' => '证件类型',
                'rules' => 'trim|in_list[0,1,2,6,A,B,C,E,F,P,BL,OTHER]',
            ),
            array(
                'field' => 'card_number_two',
                'label' => '证件号码',
                'rules' => 'trim|integer',
            ),

        );

    }

    /**
     * 处理办理入住时的逻辑, 包括房间占用, 生成账单, 发放优惠券, 证件照处理
     */
    private function handleCheckInCommonEvent($resident, $room)
    {
        //更新房间状态, 占用房间
        $this->occupiedByResident($room, $resident);

        //生成首次入住的账单
        $this->firstCheckInOrders($resident, $room);

        //发放优惠券
//        if ($request->has('normal_discount_ids')) {
//            $actRepo->assignCheckInCoupons($resident, $request->input('normal_discount_ids'));
//        }

        //部署证件照片处理任务
//        $job = (new HandleCardImages($resident))->delay(Carbon::now()->addMinutes(10));
//        dispatch($job);

        return true;
    }

    /**
     * 办理入住时, 将房间状态更新为占用状态
     */
    public function occupiedByResident($room, $resident, $status = Roomunionmodel::STATE_OCCUPIED)
    {
        if (!in_array($room->status, [Roomunionmodel::STATE_RESERVE, Roomunionmodel::STATE_BLANK])) {
            log_message('error','房间当前状态无法办理!');
            throw new \Exception('房间当前状态无法办理!');
        }

        if (Roomunionmodel::STATE_RESERVE == $room->status AND $room->resident_id != $resident->id) {
            log_message('error','该房间已经被其他人预约了!');
            throw new \Exception('该房间已经被其他人预约了!');
        }

        if (!in_array($status, [Roomunionmodel::STATE_OCCUPIED, Roomunionmodel::STATE_RESERVE])) {
            log_message('error','status 参数不合法!');
            throw new \Exception('status 参数不合法!');
        }

        return $room->update([
            'status'        => $status,
            'resident_id'   => $resident->id,
            'begin_time'    => $resident->begin_time,
            'end_time'      => $resident->end_time,
            'people_count'  => $resident->people_count ? : 0,
        ]);
    }


    /**
     * 生成首次支付订单
     */
    public function firstCheckInOrders($resident,$roomunion){

        $this->load->model('ordermodel');

        $info=[
            'number'         => $this->ordermodel->getOrderNumber(),
            'store_id'       => $roomunion->store_id,
            'room_type_id'   => $roomunion->room_type_id,
            'employee_id'    => $resident->employee_id,
            'room_id'        => $resident->room_id,
            'resident_id'    => $resident->id,
            'status'         => Ordermodel::STATE_PENDING,
            'pay_status'     => Ordermodel::PAYSTATE_PAYMENT,
            'pay_type'       => Ordermodel::PAYWAY_BANK,
            'deal'           => Ordermodel::DEAL_UNDONE,
            'created_at' => date('Y-m-d H:i:s',time()),
            'updated_at' => date('Y-m-d H:i:s',time()),
        ];


        //$first_pay_money        = $resident->first_pay_money;   //首次支付金额
        $deposit_money          = $resident->deposit_money;     //押金
        $tmp_deposit            = $resident->tmp_deposit;       //其他押金
        //$real_rent_money        = $resident->real_rent_money;   //租金
        //$real_property_price    = $resident->real_property_price;   //物业费

        //房租押金子订单
        if (0 < $deposit_money) {
            $info   = array_merge($info, [
                'money'     => $deposit_money,
                'paid'      => $deposit_money,
                'type'      => Ordermodel::PAYTYPE_DEPOSIT_R,
                'year'      => $resident->begin_time->year,
                'month'     => $resident->begin_time->month,
            ]);
            $this->ordermodel->insert($info);
        }

        //其他押金子订单
        if (0 < $tmp_deposit) {
            $info   = array_merge($info, [
                'money'     => $tmp_deposit,
                'paid'      => $tmp_deposit,
                'type'      => Ordermodel::PAYTYPE_DEPOSIT_O,
                'year'      => $resident->begin_time->year,
                'month'     => $resident->begin_time->month,
            ]);
            //Order::create($info);
            $this->ordermodel->insert($info);
        }

        //计算首次支付时的房租和物业费
        //当月还剩的天数
        $firstPay = $this->calcFirstPayMoney($resident);


        //生成物业服务费子订单
        if (0 < $resident->real_property_costs) {
            foreach ($firstPay as $bill) {
                $info   = array_merge($info, [
                    'type'      => Ordermodel::PAYTYPE_MANAGEMENT,
                    'year'      => $bill['year'],
                    'month'     => $bill['month'],
                    'money'     => $bill['management'],
                    'paid'      => $bill['management'],
                ]);
                $this->ordermodel->insert($info);
                //Order::create($info);
            }
        }

        //房租子订单
        if (0 < $resident->real_rent_money) {
            foreach ($firstPay as $bill) {
                $info   = array_merge($info, [
                    'type'      => Ordermodel::PAYTYPE_ROOM,
                    'year'      => $bill['year'],
                    'month'     => $bill['month'],
                    'money'     => $bill['rent'],
                    'paid'      => $bill['rent'],
                ]);
                $this->ordermodel->insert($info);
               // Order::create($info);
            }
        }
        return true;
    }

    /**
     * 计算并判断首次需要支付的几笔费用
     */
    private function calcFirstPayMoney($resident)
    {
        $beginTime          = $resident->begin_time;
        $payFrequency       = $resident->pay_frequency;
        $dateCheckIn        = $beginTime->day;
        $daysThatMonth      = $beginTime->copy()->endOfMonth()->day;
        $daysLeftOfMonth    = $daysThatMonth - $dateCheckIn + 1;
        $firstOfMonth       = $resident->begin_time->copy()->firstOfMonth();

        //当月剩余天数的订单
        $data[]     = array(
            'year'       => $beginTime->year,
            'month'      => $beginTime->month,
            'rent'       => ceil($resident->real_rent_money * $daysLeftOfMonth / $daysThatMonth),
            'management' => ceil($resident->real_property_costs * $daysLeftOfMonth / $daysThatMonth),
        );

        //如果是短租, 只生成当月的账单
        if ($resident->rent_type == Residentmodel::RENTTYPE_SHORT) {
            return $data;
        }

        if ($payFrequency > 1 OR $beginTime->day >= 21) {
            $i = 1;
            do {
                $tmpDate    = $firstOfMonth->copy()->addMonths($i);
                $data[] = array(
                    'year'       => $tmpDate->year,
                    'month'      => $tmpDate->month,
                    'rent'       => $resident->real_rent_money,
                    'management' => $resident->real_property_costs,
                );
            } while (++ $i < $resident->pay_frequency);
        }

        //如果是年付, 可能要有第13个月的账单
        if (12 == $payFrequency) {
            $endDate    = $resident->end_time;
            $endOfMonth = $endDate->copy()->endOfMonth();

            if ($endDate->day < $endOfMonth->day) {
                $data[] = array(
                    'year'          => $endDate->year,
                    'month'         => $endDate->month,
                    'rent'          => ceil($resident->real_rent_money * $endDate->day / $endOfMonth->day),
                    'management'    => ceil($resident->real_property_costs * $endDate->day / $endOfMonth->day),
                );
            }
        }
        return $data;
    }

    /**
     * 取消办理入住
     */
    public function destory(){
        $resident_id    = $this->input->post('resident_id',true);
        if(empty($resident_id)){
            $this->api_res(1005);
            return;
        }
        $this->load->model('residentmodel');
        $resident   = Residentmodel::find($resident_id);
        if(!$resident){
            $this->api_res(1007);
            return;
        }
        $this->load->model('roomunionmodel');
        //判断操作员工是不是公寓管理员
        if($resident->roomunion->store_id != $this->employee->store_id){
            $this->api_res(10012);
            return;
        }
        //检查房间状态是不是占用（new）
        if($resident->roomunion->status!=Roomunionmodel::STATE_OCCUPIED){
            $this->api_res(10013);
            return;
        }
        //判断住户状态是不是 NOTPAY和RESERVE
        if(!in_array($resident->status,[Residentmodel::STATE_NOTPAY,Residentmodel::STATE_RESERVE])){
            $this->api_res(10011);
            return;
        }

        //取消预定为什么是失效 而入住是删除呢
        $this->load->model('ordermodel');
        //处理预定的取消
        try{
            DB::beginTransaction();
            if(Residentmodel::STATE_RESERVE== $resident->status){

                //把住户状态改为 无效的 订单也改成无效
                $a  = $this->invalid($resident);
                $b  = $this->invalidReserve($resident);
                //更改房间状态为blank

                if($a &&($b || $b==0)){


                }else{
                    log_message('error','Resident取消预定失败');
                    DB::rollBack();
                    $this->api_res(10014);
                    return;
                }
            }else{
                if ($this->hasPaidOrders($resident)) {
                    log_message('error','住户已经有支付过的订单, 无法进行该操作！');
                    $this->api_res(10015);
                    return;
                }
                $this->load->model('couponmodel');
                //清除优惠券
                $resident->coupons()->delete();
                //清除订单
                $resident->orders()->delete();
                //删除住户信息
                $resident->delete();

            }
            $resident->roomunion->Blank();
            DB::commit();
            $this->api_res(0);
        }catch (Exception $e){
            DB::rollBack();
            throw $e;
        }
    }

    /**
     *生成住户二维码
     */
    public function showQrCode(){
        $resident_id = $this->input->post('resident_id',true);
        $this->load->helper('common');
        $this->load->model('residentmodel');
        $resident   = Residentmodel::find($resident_id);
        if(!$resident){
            $this->api_res(1007);
            return;
        }
        if($resident->status!==Residentmodel::STATE_NOTPAY){
            $this->api_res(10011);
            return;
        }
        try{
            $app        = new Application(getWechatCustomerConfig());
            $qrcode     = $app->qrcode;
            $result     = $qrcode->temporary($resident_id, 6 * 24 * 3600);
            $ticket     = $result->ticket;
            $url        = $qrcode->url($ticket);
            $this->api_res(0,['url'=>$url]);
        }catch (Exception $e){
            log_message('error',$e->getMessage());
            throw $e;
        }
    }


    /**
     * 检查手机号码的有效性
     */
    public function checkPhoneNumber($phone)
    {
        $this->load->helper('check');
        if (!isMobile($phone)) {
            log_message('debug','请检查手机号码');
            return false;
        }
        return true;
    }

    /**
     * 检查证件号码的有效性
     */
    public function checkIdCardNumber($type, $cardNumber)
    {
        $this->load->helper('check');
        if (Residentmodel::CARD_ZERO == $type AND !isIdNumber($cardNumber)) {
            log_message('debug','请检查证件号码的有效性');
            return false;
        }

        return true;
    }

    /**
     * 计算用户的合同结束时间
     * 主要是考虑到, 租房合同开始日期是某个月的月底而结束月份是2月份的情况
     */
    public function contractEndDate($checkInDateStr, $contractTime)
    {
        $checkInDate    = Carbon::parse($checkInDateStr);

        return $this->addMonths($checkInDate, $contractTime);
    }

    /**
     * 计算指定个月后的今天的日期
     * 比如, 1月31日的一个月后可能是2月28号也可能是2月29号
     */
    public function addMonths(Carbon $date, $months = 1)
    {
        $endMonth       = $date
            ->copy()
            ->startOfMonth()
            ->addMonths($months)
            ->endOfMonth();

        if ($endMonth->day >= $date->day - 1) {
            $endTime = $endMonth->startOfMonth()->addDays($date->day - 2);
        }

        return isset($endTime) ? $endTime : $endMonth;
    }

    /**
     * [取消住户办理预订]
     * @param  [type]    $resident  [住户实例]
     * @param  OrderRepo $orderRepo [Order仓库]
     * @return [type]               [Bool]
     */
    private function invalid($resident)
    {

        //更新住户状态
        $resident->status   = Residentmodel::STATE_INVALID;
        $resident->remark   = $resident->remark . ' - 未入住';
        return $resident->save();

    }

    /**
     * [住户预订之后取消, 预订费用转服务费]
     * @param  [type] $resident [住户实例]
     * @return [type]           [更新结果]
     */
    public function invalidReserve($resident)
    {
        return $resident->orders()->where([
            'resident_id'   => $resident->id,
            'type'          => Ordermodel::PAYTYPE_RESERVE,
            'status'        => Ordermodel::STATE_COMPLETED,
        ])->update([
            'type'      => Ordermodel::PAYTYPE_MANAGEMENT,
            'remark'    => $resident->name . '预订费用转服务费',
        ]);
    }

    /**
     * 检查用户是否有已经支付过的账单
     */
    public function hasPaidOrders($resident)
    {
        $query  = Ordermodel::where('resident_id', $resident->id)
            ->whereIn('status', [
                Ordermodel::STATE_CONFIRM,
                Ordermodel::STATE_COMPLETED,
            ]);

        if ($query->exists()) {
            return true;
        }

        return false;
    }


}

