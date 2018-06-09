<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/14 0014
 * Time:        15:07
 * Describe:
 */
class Newordermodel extends Basemodel{

    /**
     * 订单状态的常量
     */
    const STATE_GENERATED   = 'GENERATE';   // 后台生成账单的状态, 未发送给用户
    const STATE_AUDITED     = 'AUDITED';    // 后台生成账单的状态, 未发送给用户
    const STATE_PENDING     = 'PENDING';    // 下单之后的默认状态,等待付款
    const STATE_CONFIRM     = 'CONFIRM';    // 付完款 等待确认
    const STATE_COMPLATE    = 'COMPLATE';   // 完成
    const STATE_COMPLETED   = 'COMPLATE';   // 完成, 我不喜欢上面的错别字, 因此换一个备用的
    const STATE_REFUND      = 'REFUND';     // 退单
    const STATE_EXPIRE      = 'EXPIRE';     // 过期
    const STATE_CLOSE       = 'CLOSE';      // 关闭

    /**
     * 支付方式
     */
    const PAYWAY_JSAPI      = 'JSAPI';      // 微信支付
    const PAYWAY_BANK       = 'BANK';       // 银行卡支付
    const PAYWAY_ALIPAY     = 'ALIPAY';     // 支付宝转账
    const PAYWAY_DEPOSIT    = 'DEPOSIT';    // 押金抵扣

    /**
     * 订单类型
     */
    const PAYTYPE_ROOM          = 'ROOM';           // 租房
    const PAYTYPE_DEIVCE        = 'DEIVCE';         // 设备
    const PAYTYPE_DEVICE        = 'DEIVCE';         // 设备
    const PAYTYPE_UTILITY       = 'UTILITY';        // 水电费
    const PAYTYPE_REFUND        = 'REFUND';         // 退房
    const PAYTYPE_RESERVE       = 'RESERVE';        // 预订
    const PAYTYPE_MANAGEMENT    = 'MANAGEMENT';     // 物业服务费
    const PAYTYPE_DEPOSIT_R     = 'DEPOSIT_R';      // 房租押金
    const PAYTYPE_DEPOSIT_O     = 'DEPOSIT_O';      // 其他押金
    const PAYTYPE_OTHER         = 'OTHER';          // 其他收费
    const PAYTYPE_WATER         = 'WATER';          // 水费
    const PAYTYPE_CLEAN         = 'CLEAN';          // 清洁费
    const PAYTYPE_ELECTRICITY   = 'ELECTRICITY';    // 电费
    const PAYTYPE_COMPENSATION  = 'COMPENSATION';   // 物品赔偿费
    const PAYTYPE_REPAIR        = 'REPAIR';         // 维修服务费
    const PAYTYPE_OVERDUE       = 'OVERDUE';        // 滞纳金
    const PAYTYPE_WATER_HOT     = 'HOT_WATER';      // 热水水费

    /**
     * 首次 续费
     */
    const PAYSTATE_PAYMENT  = 'PAYMENT';    // 首次
    const PAYSTATE_RENEWALS = 'RENEWALS';   // 续费

    /**
     * 是否处理
     */
    const DEAL_DONE         = 'DONE';       // 处理
    const DEAL_UNDONE       = 'UNDONE';     // 未处理

    protected $table        = 'web_order';

    protected $fillable     = [
        'deal',
        'number',
        'sequence_number',
        'apartment_id',
        'room_type_id',
        'room_id',
        'employee_id',
        'resident_id',
        'customer_id',
        'money',
        'pay_type',
        'type',
        'other_id',
        'year',
        'month',
        'remark',
        'status',
        'paid',
        'pay_status',
    ];



    public function customer()
    {
        return $this->belongsTo(Customermodel::class, 'customer_id');
    }

    public function roomunion()
    {
        return $this->belongsTo(Roomunionmodel::class, 'room_id');
    }

    public function store()
    {
        return $this->belongsTo(Storemodel::class, 'store_id');
    }

    public function resident()
    {
        return $this->belongsTo(Residentmodel::class, 'resident_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employeemodel::class, 'employee_id');
    }

    public function roomtype()
    {
        return $this->belongsTo(Roomtypemodel::class, 'room_type_id');
    }
    public function address(){
        return $this->hasMany(Goodsaddressmodel::class,'address_id')->select('id');
    }

    //社区名
    public function storename(){

        return $this->belongsTo(Storemodel::class,'store_id')->select('id','name','created_at');
    }

    //房间号
    public function roomnum(){

        return $this->belongsTo(Roomunionmodel::class,'room_id')->select('id','number');
    }

    public function residentder(){

        return $this->belongsTo(Residentmodel::class,'resident_id')->select('id','name','deposit_money');
    }

    public function union(){
        return $this->belongsTo(Roomunionmodel::class,'room_id')->select('id','number','area');
    }

    /**
     * 生成新的订单编号
     */
    public static function newNumber($cityAbbreviation = '', $apartmentAbbreviation = '')
    {
        return strtoupper($cityAbbreviation) . strtoupper($apartmentAbbreviation) . date('YmdHis') . mt_rand(1000, 9999);
    }

    /**
     * 生成随机数作为订单编号
     */
    public static function getOrderNumber()
    {
        //    return date('YmdHis').mt_rand(1000000000, 9999999999);
        return date('YmdHis').mt_rand(100000000, 999999999);
//        return date('YmdHis').mt_rand(1, 100000);
    }

    /**
     * 返回订单类型的名称
     */
    public static function getTypeName($type = null)
    {
        $names = self::allTypesWithName();

        return isset($names[$type]) ? $names[$type] : '未知';
    }
    /**
     * 返回所有账单的名称
     */
    public static function allTypesWithName()
    {
        return [
            self::PAYTYPE_ROOM          => '住宿服务费',
            self::PAYTYPE_DEVICE        => '设备服务费',
            self::PAYTYPE_UTILITY       => '水电服务费',
            self::PAYTYPE_REFUND        => '退款',
            self::PAYTYPE_RESERVE       => '预订服务费',
            self::PAYTYPE_MANAGEMENT    => '物业服务费',
            self::PAYTYPE_DEPOSIT_R     => '住宿押金',
            self::PAYTYPE_DEPOSIT_O     => '其他押金',
            self::PAYTYPE_OTHER         => '其他服务费',
            self::PAYTYPE_ELECTRICITY      => '用电服务费',
            self::PAYTYPE_WATER         => '冷水服务费',
            self::PAYTYPE_WATER_HOT     => '热水服务费',
            self::PAYTYPE_CLEAN         => '清洁服务费',
            self::PAYTYPE_COMPENSATION  => '物品赔偿费',
            self::PAYTYPE_OVERDUE       => '滞纳金',
            self::PAYTYPE_REPAIR        => '维修服务费',
        ];
    }
    /**
     * 检索当日确定的账单的数量
     */
    public function ordersConfirmedToday()
    {
        return $this->where('deal', self::DEAL_DONE)
            ->where('status', self::STATE_COMPLETED)
            ->whereDate('updated_at', '=', date('Y-m-d'))
            ->count();
    }
    //$sequence_number   = sprintf("%s%06d", date('Ymd'), $this->ordermodel->ordersConfirmedToday()+1);

    /**
     * 生成首次支付订单
     */
    public function firstCheckInOrders($resident,$roomunion){


        $info=[
            'store_id'       => $roomunion->store_id,
            'room_type_id'   => $roomunion->room_type_id,
            'employee_id'    => $resident->employee_id,
            'uxid'           => $resident->uxid,
            'customer_id'    => $resident->customer_id,
            'room_id'        => $resident->room_id,
            'resident_id'    => $resident->id,
            'status'         => Newordermodel::STATE_PENDING,
            'pay_status'     => Newordermodel::PAYSTATE_PAYMENT,
            'pay_type'       => Newordermodel::PAYWAY_BANK,
            'deal'           => Newordermodel::DEAL_UNDONE,
            'created_at' => date('Y-m-d H:i:s',time()),
            'updated_at' => date('Y-m-d H:i:s',time()),
        ];



        $resident->begin_time   = Carbon::parse($resident->begin_time);
        $resident->end_time     = Carbon::parse($resident->end_time);

        $deposit_money          = $resident->deposit_money;     //押金
        $tmp_deposit            = $resident->tmp_deposit;       //其他押金

        //续租时的订单处理, 可能需要更改
        if (isset($resident->data['renewal'])) {
            $deposit_money       = $resident->data['renewal']['delt_deposit'];
            $tmp_deposit         = $resident->data['renewal']['new_tmp_deposit'];
        }

        //预订转入住, 订金转押金, 订金可以抵扣一部分押金
        if (0 < $resident->book_money) {
            $ordersBooking  = $resident->neworders()
                ->where([
                    'status'    => Newordermodel::STATE_COMPLETED,
                    'type'      => Newordermodel::PAYTYPE_RESERVE,
                ])
                ->get();

            $moneyBooking   = $ordersBooking->sum('paid');
            $deposit_money   = $deposit_money > $moneyBooking ?  $deposit_money - $moneyBooking : 0;

            //用ORM会自动更新updated_at, 因此在这里用DB门面操作
            Newordermodel::whereIn('id', $ordersBooking->pluck('id')->toArray())->update(['type' => Newordermodel::PAYTYPE_DEPOSIT_R]);
        }


        //房租押金子订单
        if (0 < $deposit_money) {
            $info   = array_merge($info, [
                'number'         => $this->getOrderNumber(),
                'money'     => $deposit_money,
                'paid'      => $deposit_money,
                'type'      => Newordermodel::PAYTYPE_DEPOSIT_R,
                'year'      => $resident->begin_time->year,
                'month'     => $resident->begin_time->month,
            ]);
            $this->insert($info);
        }

        //其他押金子订单
        if (0 < $tmp_deposit) {
            $info   = array_merge($info, [
                'number'         => $this->getOrderNumber(),
                'money'     => $tmp_deposit,
                'paid'      => $tmp_deposit,
                'type'      => Newordermodel::PAYTYPE_DEPOSIT_O,
                'year'      => $resident->begin_time->year,
                'month'     => $resident->begin_time->month,
            ]);
            //Order::create($info);
            $this->insert($info);
        }

        //计算首次支付时的房租和物业费
        //当月还剩的天数
        $firstPay = $this->calcFirstPayMoney($resident);


        //生成物业服务费子订单
        if (0 < $resident->real_property_costs) {
            foreach ($firstPay as $bill) {
                $info   = array_merge($info, [
                    'number'         => $this->getOrderNumber(),
                    'type'      => Newordermodel::PAYTYPE_MANAGEMENT,
                    'year'      => $bill['year'],
                    'month'     => $bill['month'],
                    'money'     => $bill['management'],
                    'paid'      => $bill['management'],
                ]);
                $this->insert($info);
                //Order::create($info);
            }
        }

        //房租子订单
        if (0 < $resident->real_rent_money) {
            foreach ($firstPay as $bill) {
                $info   = array_merge($info, [
                    'number'         => $this->getOrderNumber(),
                    'type'      => Newordermodel::PAYTYPE_ROOM,
                    'year'      => $bill['year'],
                    'month'     => $bill['month'],
                    'money'     => $bill['rent'],
                    'paid'      => $bill['rent'],
                ]);
                $this->insert($info);
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
     * [更新水电费表和物品租赁表]
     * @param  [OrderEntity]    $order  [Order 表中的实例]
     * @param  [String]         $status [新的状态]
     * @param  [String]         $deal   [新的交易结果]
     *
     * @return [integer]        [操作结果]
     */
    public function updateDeviceAndUtility($order, $status, $deal)
    {
        if (!$order->other_id) return false;

        switch ($order->type) {
            case Newordermodel::PAYTYPE_DEVICE :
                $tmpOrder   = Smartdevicemodel::find($order->other_id);
                break;
            case Newordermodel::PAYTYPE_UTILITY :
                $tmpOrder   = Utilitymodel::find($order->other_id);
                break;
            default:
                $tmpOrder   = NULL;
                break;
        }

        if (!$tmpOrder) {

            log_message('error','水电或物品订单更新失败:未查找到相应订单!');
            return false;
        }

        return $tmpOrder->update([
            'status'    => $status,
        ]);
    }

    /**
     * [检索住户未完成缴费的账单未支付及未确认完成]
     * @param  [integer] $residentId [住户id]
     * @return [OrderCollection]    [订单列表]
     */
    public function ordersUnpaidOfResident($residentId)
    {
        return Newordermodel::where('resident_id', $residentId)->whereIn('status', [Newordermodel::STATE_PENDING, Newordermodel::STATE_CONFIRM])->get();
    }


    /**
     * 添加退房时的订单
     */
    public function addCheckOutOrderByType($resident, $room, $number, $employeeId, $type, $money, Carbon $time)
    {
        $order  = new Newordermodel();
        $data   =[
            'number'        => $number,
            'resident_id'   => $resident->id,
            'room_id'       => $room->id,
            'store_id'      => $room->store_id,
            'room_type_id'  => $room->room_type_id,
            'customer_id'   => $resident->customer_id,
            'uxid'          => $resident->uxid,
            'employee_id'   => $employeeId,
            'type'          => strtoupper($type),
            'money'         => $money,
            'paid'          => $money,
            'status'        => Newordermodel::STATE_PENDING,
            'year'          => $time->year,
            'month'         => $time->month,
        ];
        $order->fill($data);
        $order->save();
        return $order;
    }

    /**
     * 获取指定住户在指定月份即之后的账单
     * 刚开始写这个是为了在退房时统计房租和住宿费的缴费情况
     */
    public function rentAndPropertyForThisMonthAndLater($residentId, Carbon $time)
    {
        return Newordermodel::where('resident_id', $residentId)
            ->where(function ($query) use ($time) {
                $query->where('year', '>', $time->year)
                    ->orWhere([
                        ['year', '=', $time->year],
                        ['month', '>=', $time->month],
                    ]);
            })
            ->whereIn('type', [
                Newordermodel::PAYTYPE_ROOM,
                Newordermodel::PAYTYPE_MANAGEMENT,
            ])
            ->get();
    }

    /**
     * 处理退房时账单用押金抵扣的部分
     */
    public function payByDeposit(array $orderIds)
    {
        return Newordermodel::whereIn('id', $orderIds)->update([
            'pay_type'  => Newordermodel::PAYWAY_DEPOSIT,
            'status'    => Newordermodel::STATE_COMPLETED,
        ]);
    }

    public function orderMoneyCheckOutInit()
    {
        return [
            Newordermodel::PAYTYPE_WATER        => 0,
            Newordermodel::PAYTYPE_ELECTRIC     => 0,
            Newordermodel::PAYTYPE_CLEAN        => 0,
            Newordermodel::PAYTYPE_COMPENSATION => 0,
            Newordermodel::PAYTYPE_ROOM         => 0,
            Newordermodel::PAYTYPE_MANAGEMENT   => 0,
        ];
    }




}
