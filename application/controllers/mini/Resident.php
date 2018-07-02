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
        $this->load->model('employeemodel');

    }

    /**
     * 检查集中式房间号 跟状态
     * 返回room_id
     */
    public function checkRoomUnion()
    {
        $store_id   = $this->employee->store_id;
        $room_number    = $this->input->post('room_number',true);
        $status    = $this->input->post('status',true);
        $where      = ['store_id'=>$store_id,'number'=>$room_number];
        $this->load->model('roomunionmodel');

        if(!$room=Roomunionmodel::where($where)->first()){
            $this->api_res(1007);
            return;
        }

        $room_id    = $room->id;
        if($room->status!=$status){
            $this->api_res(10010);
            return;
        }
        $data["room_id"]=$room_id;
        $data['rent_price']=$room->rent_price;
        $data['property_price']=$room->property_price;

        if($status=='RENT'){
            $this->load->model('residentmodel');
            $data['resident']=Residentmodel::WHERE('room_id',$room->id)->orderBy('end_time','DESC')->first();
        }

        $this->api_res(0,$data);


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
            'name','phone','card_type','card_number','card_one','card_two','card_three','real_property_costs','real_rent_money',
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

//            if((empty($post['phone_two']) || empty($post['card_number_two']) )||(( empty($post['card_type_two']))&&$post['card_number_two']!==0)){
          /*  if(empty($post['phone_two']) || empty($post['card_type_two'] || empty($post['card_number_two']))){
                $this->api_res(1002,['error'=>'住户二信息不全']);
                return;

            }*/
            if(!$this->checkPhoneNumber($post['phone_two'])){
                $this->api_res(1002,['error'=>'请检查住戶二手机号']);
                return;
            }
            if(!$this->checkIdCardNumber($post['card_type_two'],$post['card_number_two'])){
                $this->api_res(1002,['error'=>'请检查住戶二身份证号']);
                return;
            }
        }
        //获取请求参数,
        $data   = $this->handleCheckInData($post);
        //var_dump($data);die();
        //获取房间信息
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $room   = Roomunionmodel::where('store_id',$this->employee->store_id)->find($post['room_id']);
        $store  = $room->store;
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
            $resident->rent_price   = $room->rent_price;
            $resident->property_price   = $room->property_price;
            $resident->water_price   = $store->water_price;
            $resident->hot_water_price   = $store->hot_water_price;
            $resident->electricity_price   = $store->electricity_price;

           // $resident->employee_id  = $this->employee->id;
            $resident->card_one = $this->splitAliossUrl($data['card_one']);
            $resident->card_two = $this->splitAliossUrl($data['card_two']);
            $resident->card_three = $this->splitAliossUrl($data['card_three']);
            $resident->company_id = 1;
            $a=$resident->save();
            //把房间状态改成占用
            $b=$this->occupiedByResident($room, $resident);
            //$b=$this->handleCheckInCommonEvent($resident, $room);
            if($a && $b){
                DB::commit();
            }else{
                DB::rollBack();
                $this->api_res(1009);
                return;
            }
//            $this->load->model('activitymodel');
//            $this->load->model('coupontypemodel');
//            $this->load->model('contractmodel');
//            $data=$resident->transform($resident);
            //var_dump($data);
            $this->api_res(0,['resident_id'=>$resident->id]);
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
            'data'          => ['card_img_path' => [
                                                'card_one'      => $this->splitAliossUrl($data['card_one']),
                                                'card_two'      => $this->splitAliossUrl($data['card_two']),
                                                'card_three'    => $this->splitAliossUrl($data['card_three']),
                                            ],
                ],

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
                'errors' => array(
                    'required' => '请填写%s',
                ),
            ),
            array(
                'field' => 'begin_time',
                'label' => '开始时间',
                'rules' => 'required|trim',
                'errors' => array(
                    'required' => '请填写%s',
                ),
            ),
            array(
                'field' => 'people_count',
                'label' => '入住人数',
                'rules' => 'required|trim|integer',
                'errors' => array(
                    'required' => '请填写%s',
                    'integer' => '%s必须是一个整数',
                ),
            ),
            array(
                'field' => 'contract_time',
                'label' => '合同时长',
                'rules' => 'required|trim|integer',
                'errors' => array(
                    'required' => '请填写%s',
                    'integer' => '%s必须是一个整数',
                ),
            ),
            array(
                'field' => 'discount_id',
                'label' => '折扣id',
                'rules' => 'trim|integer',
                'errors' => array(
                    'integer' => '请选择正确的%s',
                ),
            ),
            array(
                'field' => 'rent_type',
                'label' => '出租类型',
                'rules' => 'trim|required|in_list[LONG,SHORT]',
                'errors' => array(
                    'required' => '请填写%s',
                    'integer' => '请选择正确的%s',
                ),
            ),
            array(
                'field' => 'real_rent_money',
                'label' => '实际租金',
                'rules' => 'trim|numeric|required',
            ),
            array(
                'field' => 'real_property_costs',
                'label' => '实际物业费',
                'rules' => 'trim|numeric|required',
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
                'errors' => array(
                    'required' => '请选择%s',
                    'numeric' => '请选择正确的%s',
                ),
            ),
            array(
                'field' => 'deposit_money',
                'label' => '押金',
                'rules' => 'trim|numeric|required',
                'errors' => array(
                    'required' => '请选择%s',
                    'numeric' => '请填写正确的%s',
                ),
            ),
            array(
                'field' => 'deposit_month',
                'label' => '押金月份',
                'rules' => 'trim|integer|required',
                'errors' => array(
                    'required' => '请选择%s',
                    'integer' => '请选择正确的%s',
                ),
            ),
            array(
                'field' => 'tmp_deposit',
                'label' => '其他押金',
                'rules' => 'trim|numeric|required',
                'errors' => array(
                    'required' => '请填写%s',
                    'integer' => '请选择正确的%s',
                ),
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
                'errors' => array(
                    'required' => '请填写%s',
                ),
            ),
            array(
                'field' => 'phone',
                'label' => '手机号',
                'rules' => 'required|trim|max_length[13]|numeric',
                'errors' => array(
                    'required' => '请填写%s',
                    'max_length'    => '请检查手机号',
                    'numeric'    => '请检查手机号',
                ),
            ),
            array(
                'field' => 'card_type',
                'label' => '证件类型',
                'rules' => 'required|trim|in_list[0,1,2,6,A,B,C,E,F,P,BL]',
                'errors' => array(
                    'required' => '请填写%s',
                    'in_list'    => '请选择正确的证件类型',
                ),
            ),
            array(
                'field' => 'card_number',
                'label' => '证件号码',
                'rules' => 'required|trim',
                'errors' => array(
                    'required' => '请填写%s',
                ),
            ),
            array(
                'field' => 'alternative',
                'label' => '紧急联系人',
                'rules' => 'required|trim|max_length[13]',
                'errors' => array(
                    'required' => '请填写%s',
                ),
            ),
            array(
                'field' => 'alter_phone',
                'label' => '联系方式',
                'rules' => 'required|trim|numeric',
                'errors' => array(
                    'required' => '请填写%s',
                    'numeric'   =>'请填写正确的联系方式',
                ),
            ),
            array(
                'field' => 'address',
                'label' => '通讯地址',
                'rules' => 'required|trim',
                'errors' => array(
                    'required' => '请填写%s',
                ),
            ),
            array(
                'field' => 'card_one',
                'label' => '证件照1',
                'rules' => 'required|trim',
                'errors' => array(
                    'required' => '请上传%s',
                ),
            ),
            array(
                'field' => 'card_two',
                'label' => '证件照2',
                'rules' => 'required|trim',
                'errors' => array(
                    'required' => '请上传%s',
                ),
            ),
            array(
                'field' => 'card_three',
                'label' => '证件照3',
                'rules' => 'required|trim',
                'errors' => array(
                    'required' => '请上传%s',
                ),
            ),
            /*array(
                'field' => 'name_two',
                'label' => '住户名称',
                'rules' => 'trim',
            ),
            array(
                'field' => 'phone_two',
                'label' => '手机号',
                'rules' => 'trim|numeric|max_length[13]',
            ),
            array(
                'field' => 'card_type_two',
                'label' => '证件类型',
                'rules' => 'trim|in_list[0,1,2,6,A,B,C,E,F,P,BL,OTHER]',
            ),
            array(
                'field' => 'card_number_two',
                'label' => '证件号码',
                'rules' => 'trim',
            ),*/

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

            throw new \Exception('房间当前状态无法办理!');
        }

        if (Roomunionmodel::STATE_RESERVE == $room->status AND $room->resident_id != $resident->id) {

            throw new \Exception('该房间已经被其他人预约了!');
        }

        if (!in_array($status, [Roomunionmodel::STATE_OCCUPIED, Roomunionmodel::STATE_RESERVE])) {

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
    public function destroy(){
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

    /**
     * 办理预订
     */
    public function reservation()
    {
        $field  = ['room_id','name','phone','book_money','book_time','remark'];
        //表单验证
        if(!$this->validationText($this->validateReservation())){

            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $input  = $this->input->post(null,true);
        $room_id    = $input['room_id'];
        $name       = $input['name'];
        $phone      = $input['phone'];
        $book_money = $input['book_money'];
        $book_time  = $input['book_time'];
        $remark     = isset($input['remark'])?$input['remark']:'';
        if(!$this->checkPhoneNumber($phone)){

            $this->api_res(1002,['error'=>'请检查手机号']);
            return;
        }
        $this->load->model('roomunionmodel');
        $room   = Roomunionmodel::find($room_id);
        if(!$room){
            $this->api_res(1007);
            return;
        }
        if(!$room->isBlank()){
            $this->api_res(10010);
            return;
        }
        $this->load->model('residentmodel');
        $resident   = new Residentmodel();
        $this->load->model('ordermodel');
        $order   = new Ordermodel();
        try{
            //开启事务
            DB::beginTransaction();
            //生成住户
            $resident->room_id  = $room_id;
            $resident->name     = $name;
            $resident->phone        = $phone;
            $resident->book_money   = $book_money;
            $resident->book_time    = Carbon::parse($book_time);
            $resident->begin_time   = Carbon::now();
            $resident->end_time     = Carbon::now();
            $resident->employee_id  = $this->employee->id;
            $resident->status       = Residentmodel::STATE_NOTPAY;
            $resident->remark       = $remark;
            $resident->store_id     = $this->employee->store_id;
            $resident->company_id   = 1;
            $a  = $resident->save();
            //更新房间状态
            $this->occupiedByResident($room, $resident);
           // $room->Occupie();
            //生成订单
            $order->number      = $order->getOrderNumber();
            $order->resident_id = $resident->id;
            $order->employee_id = $resident->employee_id;
            $order->money       = $resident->book_money;
            $order->paid        = $resident->book_money;
            $order->year        = $resident->book_time->year;
            $order->month       = $resident->book_time->month;
            $order->remark      = $resident->remark;
            $order->room_id     = $room_id;
            $order->store_id    = $room->store_id;
            $order->room_type_id= $room->room_type_id;
            $order->status      = Ordermodel::STATE_PENDING;
            $order->deal        = Ordermodel::DEAL_UNDONE;
            $order->type        = Ordermodel::PAYTYPE_RESERVE;
            $b  = $order->save();
            if($a&&$b){
                DB::commit();
                $this->load->model('activitymodel');
                $this->load->model('coupontypemodel');
                $this->load->model('contractmodel');
                $this->api_res(0,['data'=>$resident->transform($resident)]);
            }else{
                DB::rollBack();
                $this->api_res(1009);
                return;
            }
        }catch (Exception $e){
            DB::rollBack();
            throw  $e;
        }

    }

    /**
     * 办理预订表单验证
     */
    public function validateReservation(){
        return array(
            array(
                'field' => 'room_id',
                'label' => '房间id',
                'rules' => 'required|trim|integer',
                'errors' => array(
                    'required' => '请选择房间',
                ),
            ),
            array(
                'field' => 'name',
                'label' => '用户姓名',
                'rules' => 'required|trim|max_length[32]',
                'errors' => array(
                    'required' => '请填写%s',
                ),
            ),
            array(
                'field' => 'phone',
                'label' => '用户手机号码',
                'rules' => 'required|trim|max_length[11]',
                'errors' => array(
                    'required' => '请填写%s',
                    'max_length' => '请检查%s'
                ),
            ),
            array(
                'field' => 'book_money',
                'label' => '定金',
                'rules' => 'required|trim|numeric',
                'errors' => array(
                    'required' => '请填写%s',
                    'numeric' => '请填写正确的%s'
                ),
            ),
            array(
                'field' => 'book_time',
                'label' => '订房日期',
                'rules' => 'required|trim',
                'errors' => array(
                    'required' => '请填写%s',
                ),
            ),
            array(
                'field' => 'remark',
                'label' => '备注',
                'rules' => 'trim'
            ),
        );
    }

    /**
     * 获取住户信息
     */
    public function getResident(){
//        $field  = [
//            'begin_time','real_rent_money','real_property_costs','first_pay_money','room_id',
//            'contract_time','discount_id','name','phone','deposit_money','deposit_month','tmp_deposit'
//            ,'pay_frequency',
//        ];
        $resident_id   = $this->input->post('resident_id',true);
        $this->load->model('residentmodel');
        $resident   = Residentmodel::find($resident_id);
        if(!$resident){
            $this->api_res(1007);
            return;
        }
        $this->load->model('roomunionmodel');
        $this->load->model('activitymodel');
        $this->load->model('coupontypemodel');
        $this->load->model('contractmodel');
        $this->load->model('ordermodel');
        $this->load->model('customermodel');
        $data=$resident->transform($resident);
        $data['card_one_url']   = $this->fullAliossUrl($data['card_one_url'] );
        $data['card_two_url']   = $this->fullAliossUrl($data['card_two_url'] );
        $data['card_three_url']   = $this->fullAliossUrl($data['card_three_url'] );
        $this->api_res(0,['data'=>$data]);
    }

    /**
     * 本公寓住户列表
     * 可选携带参数, status, page, per_page, room_number  都是可选参数
     */
    public function listResident()
    {
        if(!$this->validationText($this->validateListRequest())){
            $this->api_res(1002,['error'=>$this->form_first_error()]);
            return;
        }
        $store_id   = $this->employee->store_id;
        $where  = ['store_id'=>$store_id];
        $input  = $this->input->post(null,true);
        isset($input['status'])&&$input['status']?$where['status']=$input['status']:null;
        $page   = (int)(isset($input['page'])?$input['page']:1);
        $per_page   = isset($input['per_page'])?$input['per_page']:PAGINATE;
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        if(isset($input['room_number'])){
            $ids   = Roomunionmodel::where([
                'store_id'=>$store_id,
                'number'=>$input['room_number']
            ])
                ->where('resident_id', '>', 0)
                ->pluck('resident_id')
                ->toArray();
        }else{
            $ids    = Roomunionmodel::where([
                'store_id'=>$store_id,
            ])
                ->where('resident_id', '>', 0)
                ->pluck('resident_id')
                ->toArray();
        }
        $total_page = ceil(Residentmodel::where($where)->whereIn('id',$ids)->count()/$per_page);
        if($page>$total_page){
            $this->api_res(0,['total_page'=>$total_page,'current_page'=>$page,'per_page'=>$per_page,'residents'=>[]]);
            return;
        }
        $residents  = Residentmodel::with('roomunion')->where($where)->whereIn('id',$ids)
            ->offset(($page-1)*$per_page)->limit($per_page)
            ->orderBy('end_time', 'ASC')->orderBy('room_id', 'ASC')
            ->get();
        $this->api_res(0,['total_page'=>$total_page,'current_page'=>$page,'per_page'=>$per_page,'residents'=>$residents]);
    }

    private function validateListRequest()
    {

        return array(

            array(
                'field' => 'status',
                'label' => '住户状态',
                'rules' => 'trim|in_list[RESERVE,NORMAL,NOT_PAY,NORMAL_REFUND,UNDER_CONTRACT,RENEWAL,CHANGE_ROOM]',
            ),
            array(
                'field' => 'page',
                'label' => '页码',
                'rules' => 'trim|integer',
            ),
            array(
                'field' => 'per_page',
                'label' => '每页条数',
                'rules' => 'trim|integer',
            ),
            array(
                'field' => 'room_number',
                'label' => '房间号',
                'rules' => 'trim',
            ),
        );
    }

    /**
     * 预订的房间转办理入住
     * 传入resident_id 可能所选的房间跟之前预定的不一样
     */
    public function bookingToCheckIn()
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
        $post  = $this->input->post(null,true);
        $resident_id    = trim(strip_tags($post['resident_id']));
        $this->load->model('residentmodel');
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
            /*if(empty($post['phone_two']) || empty($post['card_type_two'] || empty($post['card_number_two']))){
                $this->api_res(1002,['error'=>'住户二信息不全']);
                return;

            }*/
            if(!$this->checkPhoneNumber($post['phone_two'])){
                $this->api_res(1002,['error'=>'请检查住戶二手机号']);
                return;
            }
            if(!$this->checkIdCardNumber($post['card_type_two'],$post['card_number_two'])){
                $this->api_res(1002,['error'=>'请检查住戶二身份证号']);
                return;
            }
        }
        //获取请求参数,
        $data   = $this->handleCheckInData($post);
        //判断用户状态
        $resident   = Residentmodel::find($resident_id);
        if($resident->status!=Residentmodel::STATE_RESERVE){
            $this->api_res(10011);
            return;
        }
        //获取房间信息
        $this->load->model('roomunionmodel');
        $room    = Roomunionmodel::where(['store_id'=>$this->employee->store_id])->find($data['room_id']);
        if(!$room){
            $this->api_res(1007);
            return;
        }
        try{
            DB::beginTransaction();
            //如果入住的房间和预订的房间不一样, 要将原房间置空
            if ($data['room_id'] == $resident->room_id) {
                //一样的时候判断房间状态是不是预定
                if($room->status!=Roomunionmodel::STATE_RESERVE){
                    $this->api_res(10021);
                    return;
                }
            } else {
                //与预定时候房间不一样时
                if(!$room->isBlank()){
                    $this->api_res(10010);
                    return;
                }
                //需要将原房间置空
                $old_room   = $resident->roomunion;
                $old_room->status   = Roomunionmodel::STATE_BLANK;
                $old_room->people_count  = 0;
                $old_room->resident_id   = 0;
                $old_room->save();
            }
            $resident->fill($data);
            $resident->rent_price   = $room->rent_price;
            $resident->property_price   = $room->property_price;

            // $resident->employee_id  = $this->employee->id;
            $resident->card_one = $this->splitAliossUrl($data['card_one']);
            $resident->card_two = $this->splitAliossUrl($data['card_two']);
            $resident->card_three = $this->splitAliossUrl($data['card_three']);
            $a=$resident->save();
            //把房间状态改成占用
            $b=$this->occupiedByResident($room, $resident);
            //$b=$this->handleCheckInCommonEvent($resident, $room);
            if($a && $b){
                DB::commit();
            }else{
                DB::rollBack();
                $this->api_res(1009);
                return;
            }
            $this->api_res(0,['resident_id'=>$resident->id]);
        }catch (Exception $e){
            DB::rollBack();
            throw $e;
        }

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

//    /**
//     * 未完成订单（生成住户，住户未扫描）
//     */
//    public function unConfirm()
//    {
//        $input  = $this->input->post(null,true);
//        $page   = (int)(isset($input['page'])?$input['page']:1);
//        $per_page   = isset($input['per_page'])?$input['per_page']:PAGINATE;
//        $offset = ($page-1)*PAGINATE;
//        $store_id   = $this->employee->id;
//        $where  = ['store_id'=>$store_id];
//        $where=[];
//        isset($input['room_number'])?$where['number']=$input['room_number']:null;
//        $this->load->model('roomunionmodel');
//        $this->load->model('residentmodel');
//        $data   = Roomunionmodel::with('resident')->where($where)->where('resident_id','>',0)
//            ->get()->where('resident.customer_id',0);
//        $total  = ceil(count($data)/$per_page);
//
//        $count  = count($data);
//
//        $list   = Roomunionmodel::with('resident')->offset($offset)->limit($per_page)->where($where)->where('resident_id','>',0)
//            ->get()->where('resident.customer_id',0);
//        //$list   = $data->forPage($page,$per_page)->toArray();
//
//
//        $this->api_res(0,['total_page'=>$total,'count'=>$count,'page'=>$page,'data'=>$list]);
//    }



}


