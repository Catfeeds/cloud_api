<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Illuminate\Database\Capsule\Manager as DB;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/22 0022
 * Time:        18:51
 * Describe:    订单
 *
 */
class Order extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 检索某个房间下的订单, 用于未交费支付时显示
     */
    public function showByRoom()
    {

        $input  = $this->input->post(null,true);
        $room_id    = $input['room_id'];
        $resident_id    = $input['resident_id'];
        $status     = $input['status'];
        $this->load->model('roomunionmodel');
        $this->load->model('ordermodel');
        $this->load->model('residentmodel');

        $room   = Roomunionmodel::where('store_id',$this->employee->store_id)->find($room_id);
//        $room   = Roomunionmodel::find(126);

        if(empty($room))
        {
            $this->api_res(1007);
            return;
        }

        $resident   = $room->resident;

        $orders = $resident->orders()->where('status',$status)->get();

        $totalMoney = number_format($orders->sum('money'),2);

        $this->api_res(0,['totalMoney'=>$totalMoney,'orders'=>$orders,'resident'=>$resident,'room'=>$room]);
    }

    /**
     * 微信缴费订单确认页面
     */

    /**
     * [根据所选的订单获取能使用的优惠券]
     */
    public function getAvailableCoupons()
    {

        $input  = $this->input->post(null,true);
        $resident_id    = $input['resident_id'];
        $order_ids      = $input['order_ids'];
//        log_message('error','TYPE'.gettype($order_ids));
//        log_message('error','TYPE'.$order_ids);
        $orderIds   = $this->getRequestIds( $order_ids);
        $this->load->model('residentmodel');
        $this->load->model('ordermodel');
        $this->load->model('couponmodel');
        $resident= Residentmodel::find($resident_id);
        $orders     = $this->undealOrdersOfSpecifiedResident($resident, $orderIds);

        $coupons    = $this->couponmodel->queryByOrders($resident,$orders);

        $this->api_res(0,['coupons'=>$coupons]);
    }


    /**
     * 订单列表
     * 根据不同的query返回不同的值
     * 可选参数包括type, status, room_number
     */
    public function listOrder()
    {
        //验证表单
        //根据状态 未支付，已支付，等待确认...
        //根据类型  房间 物业费 ...
        //根据支付方式

        $field  = ['status','type','room_number'];
        if(!$this->validationText($this->validateList())){
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $input=$this->input->post(null,true);
//        $where['store_id']= $this->employee->store_id;
        $where['room_id']=126;
        $page   = isset($input['page'])?intval(strip_tags(trim($input['page']))):1;
        $per_page   = isset($input['per_page'])?intval(strip_tags(trim($input['per_page']))):PAGINATE;
        if(isset($input['status'])){
            $where['status']    = $input['status'];
        }

        if(isset($input['type'])){
            $where['type']    = $input['type'];
        }

        $this->load->model('roomunionmodel');
        $this->load->model('ordermodel');
        $this->load->model('residentmodel');
        $this->load->model('customermodel');

        if(isset($input['room_number'])){
            $room   = Roomunionmodel::where([
                'store_id'=>$this->employee->store_id,
                'number'=>$input['room_number']
            ])->first();
            if(empty($room)){
                $this->api_res(0,['data'=>[]]);
                return;
            }
            $where['room_id']  = $room->id;
        }

        $data   = $this->ordermodel->ordersOfRooms($where,$page,$per_page);

        $this->api_res(0,['data'=>$data]);
    }



    /**
     *  订单列表筛选的规则
     */
    private function validateList()
    {

        return Array(

            array(
                'field' => 'status',
                'label' => '订单状态',
                'rules' => 'trim|in_list[GENERATE,AUDITED,PENDING,CONFIRM,COMPLATE,CLOSE]',
            ),
            array(
                'field' => 'type',
                'label' => '订单类型',
                'rules' => 'trim|in_list[ROOM,CLEAN,DEIVCE,UTILITY,REFUND,RESERVE,MANAGEMENT,OTHER,DEPOSIT_O,DEPOSIT_R,WATER,ELECTRICITY,COMPENSATION]',
            ),
            array(
                'field' => 'room_number',
                'label' => '房间号',
                'rules' => 'trim',
            ),
        );

    }

    /**
     * 微信支付订单确认
     * 用户微信支付后, 需要员工进行确认
     */
    public function confirm()
    {
        //resident_id     : 订单所属住户记录的id
        //room_id         : 订单所属房间记录的id
        //order_ids       : 操作的订单id数组, 数组, 数组, 数组
        $input  = $this->input->post(null,true);
        $resident_id    = $input['resident_id'];
        $room_id        = $input['room_id'];
        //$store_pay_id   = $input[];
        $order_ids      = $input['order_ids'];

        $this->load->model('residentmodel');
        $this->load->model('ordermodel');
        $this->load->model('contractmodel');
        $this->load->model('billmodel');
        $this->load->model('storepaymodel');
        $resident   = Residentmodel::where('room_id',$room_id)->find($resident_id);
        if(!$resident){
            $this->api_res(1007);
            return;
        }
        $orderIds   = $this->getRequestIds( $order_ids);
        $orders     = $this->undealOrdersOfSpecifiedResident($resident, $orderIds,true);
        if(!$orders){
            $this->api_res(10016);
            return;
        }

        //检查是否有合同, 是否能继续进行
        $contract   = $this->checkContract($resident);
        if(!$contract){
            $this->api_res(10017);
            return;
        }

        try{

            DB::beginTransaction();

            $this->load->model('smartdevicemodel');
            $this->load->model('utilitymodel');

            //更新订单订单到完成的状态
            $orders     = $this->completeOrders($orders,null,$resident);

            //销券
            $this->load->model('couponmodel');
            $this->couponmodel->invalidByOrders($orderIds);

            //处理房间及住户的状态
            $this->load->model('roomunionmodel');
            $this->load->model('Checkoutmodel');
            $this->updateRoomAndResident($orders, $resident, $resident->roomunion);

            DB::commit();
        }catch (Exception $e){

            DB::rollBack();
            throw $e;
        }
        $this->api_res(0);
    }

    /**
     * [获取某住户的指定未支付账单, 请求参数中需要有id的数组]
     * @param  [ResidentEntity]  $resident [Resident实例]
     * @param  Request $request  [请求]
     *
     * @return [OrderCollection]           [Order集合]
     */
    private function undealOrdersOfSpecifiedResident($resident, $order_ids, $confirm = false)
    {
        $status     = Ordermodel::STATE_PENDING;

        if ($confirm) {
            $status     = Ordermodel::STATE_CONFIRM;
        }
        $orderIds   = $order_ids;
        $orders     = $resident->orders()
            ->whereIn('id', $orderIds)
            ->where('status', $status)
            ->get();
        if (count($orders) != count($orderIds)) {
            log_message('error','未找到订单信息或者订单状态错误!');
            return false;
        }

        return $orders;
    }

    /**
     * 确认订单以及现场支付时的订单状态的更新
     * 目前的情况下, 如果订单中包含某些特定类型(水电, 物品等费用)订单, 需要同时处理掉
     * 其余订单的更新还需要补充
     */
    private function completeOrders($orders, $payWay = null,$resident)
    {

        $status     = Ordermodel::STATE_COMPLETED;
        $deal       = Ordermodel::DEAL_DONE;

        $this->createBill($orders,$payWay);


       $groups = $orders->groupBy('store_pay_id');

        foreach ($groups as $key=>$orders)
        {

            //更新订单状态
            foreach ($orders as $order) {

                //如果是水电或者物品租赁账单, 需要更新相应记录
                $this->ordermodel->updateDeviceAndUtility(
                    $order,
                    Ordermodel::STATE_COMPLETED,
                    Ordermodel::DEAL_DONE
                );

                $order->pay_type            = $payWay ? $payWay: $order->pay_type;
                $order->status              = $status;
                $order->deal                = $deal;
                $order->save();
            }

        }
        return $orders;
    }
    /**
     * [判断合同是否存在, 检查能否进行接下来的操作]
     * @param  [ResidentEntity]     $resident     [住户的实例]
     * @param  ResidentRepo         $residentRepo
     *
     * @return [bool]               [结果]
     */
    private function checkContract($resident)
    {
        //没有办理入住的时候, 合同时长是0, 这个时候不需要合同, 如果住户的状态是已经支付过的, 也不用检查
        if (0 == $resident->contract_time OR Residentmodel::STATE_NORMAL == $resident->status) {
            return true;
        }

        if (empty($resident->contract)) {
            log_message('error','未检测到该住户的合同, 请生成后重试!');
            return false;
            //throw new \Exception('未检测到该住户的合同, 请生成后重试!');
        }

        return true;
    }

    /**
     * [获取某住户指定的未使用的优惠券, 请求参数中需要有id的数组]
     * @param  [ResidentEntity]  $resident [Resident实例]
     * @param  [Request]         $request  [请求]
     *
     * @return [CouponCollection]          [Order集合]
     */
    private function unusedCouponsOfSpecifiedResident($resident,$couponIds)
    {

        $coupons    = $resident->coupons()
            ->whereIn('id', $couponIds)
            ->where('status', Couponmodel::STATUS_UNUSED)
            ->get();

        if (count($coupons) != count($couponIds)) {
            return false;
        }
        return $coupons;
    }

    /**
     * 获取请求参数中的id数组
     */
    private function getRequestIds($ids)
    {
        $ids    = explode(',',$ids);

//        if (!is_array($ids)) {
//            throw new \Exception('请检查参数: ids的参数类型应该为数组');
//        }
//
//        foreach ($ids as $id) {
//            if (!is_numeric($id)) {
//                throw new \Exception('请检查参数: id应该为整型');
//            }
//        }

        return $ids;
    }

    /**
     * 订单确认后房间状态和住户状态的更新
     * 房间 占用->预订, 占用->出租, 欠费->出租
     * 住户 未付款->预订, 未付款->出租
     */
    private function updateRoomAndResident($orders, $resident, $room)
    {
        log_message('error','TEST3');
        //检索住户是否仍有未缴费的账单, 如果仍有需缴费的账单, 则不更新
        $ordersUnpaid   = $this->ordermodel->ordersUnpaidOfResident($resident->id);

        if (count($ordersUnpaid)) {
            return true;
        }

        //判断用户是否有退房记录, 如果有退房记录, 将其标记为已支付待办理状态
        if ($record = $resident->checkout_record) {
            if (in_array($record->status, [Checkoutmodel::STATUS_APPLIED, Checkoutmodel::STATUS_UNPAID])) {
                $record->status     = Checkoutmodel::STATUS_PENDING;
                $record->save();
            }
        }

        $residentToUpdate   = Residentmodel::STATE_NOTPAY == $resident->status;
        $roomToUpdate       = in_array($room->status, [
            Roomunionmodel::STATE_ARREARS,
            Roomunionmodel::STATE_OCCUPIED,
            Roomunionmodel::STATE_RESERVE,
        ]);

        if (!$roomToUpdate AND !$residentToUpdate) {
            return true;
        }

        //判断是否是预订账单
        if ($orders->where('type', Ordermodel::PAYTYPE_RESERVE)->count() == count($orders)) {
            $roomNewStatus      = Roomunionmodel::STATE_RESERVE;
            $residentNewStatus  = Residentmodel::STATE_RESERVE;
        } else {
            $roomNewStatus      = Roomunionmodel::STATE_RENT;
            $residentNewStatus  = Residentmodel::STATE_NORMAL;
        }

        if ($roomToUpdate) {
            $room->update(['status' => $roomNewStatus]);
        }
        log_message('error',2);
        //换房等逻辑, 这里需要修改
        if ($residentToUpdate) {
            if (isset($resident->data['change_room'])) {
                //处理换房的事务
                //dispatch(new EndChangeRoomStuff($resident));
            } elseif (isset($resident->data['renewal'])) {
                //do something to handle renew stuff
            } else {
                log_message('error',1);
                $resident->update(['status' => $residentNewStatus]);
            }
        }

        return true;
    }

    /**
     * 根据参数检索订单
     * 可能是根据房间出发检索订单, 也可能是根据住户出发检索订单
     * $object 可能是Room的实例, 也可能是Resident的实例
     */
    private function queryOrders(Request $request, $object)
    {
        $this->checkStatusAndType($request->all());

        $orders     = $object->orders()->where('apartment_id', $this->authUser->apartment_id);

        if ($request->has('status')) {
            $orders = $orders->where('status', $request->input('status'));
        }

        if ($request->has('type')) {
            $orders = $orders->where('type', $request->input('type'));
        }

        if ($request->has('pay_way')) {
            $orders = $orders->where('pay_type', $request->input('pay_way'));
        }

        return $orders->orderBy('updated_at', 'DESC')->orderBy('type', 'ASC')->get();
    }

    /**
     * 现场支付
     * 现场支付, 订单状态直接转变为完成
     * 携带参数包括room_id, order_ids, coupon_ids, pay_way, resident_id
     */
    public function pay()
    {
        //现场支付
        //resident_id     : 订单所属住户记录的id
        //room_id         : 订单所属房间记录的id
        //pay_way         : 现场支付的支付方式, 枚举类型, 可选范围(BANK:刷卡,ALIPAY:支付宝转账)
        //order_ids       : 被操作订单的id数组, 是数组
        //coupon_ids      : (选填)使用的优惠券的id数组, 此记录也是数组, 不使用则不填

        $input = $this->input->post(null, true);

        $payWay         = $input['pay_way'];
        $room_id        = $input['room_id'];
        $coupon_ids     = $input['coupon_ids'];
        $order_ids      = $input['order_ids'];
        $resident_id    = $input['resident_id'];

        $this->load->model('residentmodel');
        $this->load->model('ordermodel');
        $this->load->model('contractmodel');
        $this->load->model('couponmodel');
        $this->load->model('coupontypemodel');
        $this->load->model('roomunionmodel');
        $this->load->model('checkoutmodel');
        $this->load->model('billmodel');
        $this->load->model('storepaymodel');
        $resident = Residentmodel::where('room_id', $room_id)->find($resident_id);
        if (!$resident) {
            $this->api_res(1007);
            return;
        }
        if (!in_array($payWay, $this->ordermodel->getAllPayTypes())) {
            log_message('error', '不支持的支付方式!');
            $this->api_res(10018);
            return;
        }
        //检查是否有合同, 是否能继续进行
        $contract = $this->checkContract($resident);
        if (!$contract) {
            $this->api_res(10017);
            return;
        }

        //获取订单列表与使用的优惠券列表
        $orderIds = $this->getRequestIds($order_ids);
        $orders = $this->undealOrdersOfSpecifiedResident($resident, $orderIds);
        if (!$orders) {
            $this->api_res(10016);
            return;
        }


        if(!empty($coupon_ids)){
            $couponIds = $this->getRequestIds($coupon_ids);
//            log_message('error',count($couponIds).json_encode($couponIds));
            $coupons = $this->unusedCouponsOfSpecifiedResident($resident, $couponIds);
            if (!$coupons) {
            $this->api_res(10019);
            return;
            }
        }else{
            $coupons=null;
        }



        try {
            DB::beginTransaction();
            //如果有使用优惠券, 检查优惠券是否可以使用
            $this->checkCoupons($resident, $orders, $coupons);
            //将优惠券与订单绑定, 同时更新优惠券的状态
            $this->couponmodel->bindOrdersAndCalcDiscount($resident, $orders, $coupons, true);
            //更新订单状态
            $orders = $this->completeOrders($orders, $payWay,$resident);
            //房间, 住户, 优惠券以及其他订单表的状态
            $this->updateRoomAndResident($orders, $resident, $resident->roomunion);

            DB::commit();
            $this->api_res(0);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * [检查优惠券的有效性]
     * @param  [type]     $resident   [description]
     * @param  [type]     $orders     [description]
     * @param  [type]     $coupons    [description]
     * @param  CouponRepo $couponRepo [description]
     * @return [type]                 [description]
     */
    private function checkCoupons($resident, $orders, $coupons)
    {
        //没有优惠券, 则直接返回
        if (0 == count($coupons)) return true;

        //获取订单可以使用的优惠券
        $couponsAvailable   = $this->couponmodel->queryByOrders($resident, $orders);
        $idsAvailable       = collect($couponsAvailable)->pluck('id')->toArray();

        //检查优惠券的有效性
        $coupons->map(function ($coupon) use ($idsAvailable) {
            if (!in_array($coupon->id, $idsAvailable)) {

                throw new \Exception('有不可用的优惠券, 请检查后重试!');
            }
        });

        return true;
    }

    /**
     *
     * 创建生成流水账单
     * 根据流水账单来记录用户的每次支付记录
     *
     */

    private function createBill($orders,$payWay=null)
    {
        $this->load->model('billmodel');
        $bill       = new Billmodel();
        $bill->id     =    '';
        $count      = $this->billmodel->ordersConfirmedToday()+1;
        $dateString = date('Ymd');
        $this->load->model('residentmodel');


        $bill->sequence_number     =   sprintf("%s%06d", $dateString, $count);
        $bill->store_id            =    $orders[0]->store_id;
//        $bill->employee_id         =    $orders[0]->employee_id;
        $bill->employee_id         =    $this->employee->id;
        $bill->resident_id         =    $orders[0]->resident_id;
        $bill->customer_id         =    $orders[0]->customer_id;
        $bill->uxid                =    $orders[0]->uxid;
        $bill->room_id             =    $orders[0]->room_id;
        $orderIds=array();

        $change_resident = false;
        foreach($orders as $order){

            $orderIds[]=$order->id;
            $bill->money               =    $bill->money+$order->paid;
//            if($order->pay_type=='REFUND'){
//                $bill->type                =    'OUTPUT';
//            }else{
//                $bill->type                =    'INPUT';
//            }
            if($order->pay_type=='ROOM'){
                $change_resident=true;
            }
        }
        if($change_resident){
            $Resident=Residentmodel::find($orders[0]->resident_id);
            $Resident_time=substr($Resident['begin_time'],0,7);
            if($Resident_time==substr($orders[0]->pay_type,0,7)){
                Residentmodel::where('id', $orders[0]->resident_id)->update(['status' => 'NORMAL']);
            }
        }

        $bill->pay_type            =   $payWay?$payWay:$orders[0]->pay_type;
        $bill->confirm             =    '';
        $bill->pay_date            =    date('Y-m-d H:i:s',time());
        $bill->data                =    '';
        $bill->confirm_date        =    date('Y-m-d H:i:s',time());

        //如果是微信支付
        $bill->out_trade_no='';
        $bill->store_pay_id='';

        $res=$bill->save();
        if(isset($res)){
            Ordermodel::whereIn('id', $orderIds)->update(['sequence_number' => $bill->sequence_number]);
        }
        return $res;
    }

}