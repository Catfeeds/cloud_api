<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/30 0030
 * Time:        19:32
 * Describe:
 */
class Checkoutmodel extends Basemodel {
    protected $table = 'boss_checkout_record';

    protected $CI;

    protected $fillable = [
        'room_id',
        'resident_id',
        'store_id',
        'employee_id',
        'pay_or_not',
        'type',
        'deduction',
        'status',
        'time',
    ];

    protected $dates = ['time', 'created_at', 'updated_at','handle_time'];

    protected $casts = [
        'data' => 'array',
    ];

    const STATUS_APPLIED            = 'APPLIED';    //用户申请退房
    const STATUS_CONFIRM            = 'CONFIRM';    //员工已确认处理但未完成验房
    const STATUS_CHECKED            = 'CHECKED';    //生成退房记录，未提交审核（已验房）
    const STATUS_SIGNING            = 'SIGNING';    //签署中
    const STATUS_SIGNATURE          = 'SIGNATURE';  //已签字（用户签署）
    const STATUS_AUDIT              = 'AUDIT';      //待审核（审核中）
    const STATUS_UNPAID             = 'UNPAID';     //审核通过未付款
    const STATUS_CLOSED             = 'CLOSED';     //已关闭退房单
    const STATUS_COMPLETED          = 'COMPLETED';  //已完成
    const STATUS_UNAPPROVED         = 'UNAPPROVED'; //审核未通过驳回
    //'APPLIED','UNPAID','PENDING','BY_MANAGER','MANAGER_APPROVED','PRINCIPAL_APPROVED','COMPLETED','AUDIT'


    const STATUS_PENDING            ='PENDING';                 //检查一下准备废弃
    const STATUS_BY_MANAGER         ='BY_MANAGER';              //检查一下准备废弃
    const STATUS_MANAGER_APPROVED           ='MANAGER_APPROVED';//检查一下准备废弃
    const STATUS_PRINCIPAL_APPROVED         ='PRINCIPAL_APPROVED';//检查一下准备废弃


    const TYPE_NORMAL               = 'NORMAL_REFUND';  //正常退房
    const TYPE_ABNORMAL             = 'UNDER_CONTRACT'; //违约退房
    const TYPE_NOLIABILITY          = 'NO_LIABILITY';   //免责退房

    const CREATER_ROLE_EMPLOYEE     = 'EMPLOYEE';
    const CREATER_ROLE_CUSTOMER     = 'CUSTOMER';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->CI   = &get_instance();
    }

    /**
     * 退房记录所属房间
     */
    public function roomunion() {
        return $this->belongsTo(Roomunionmodel::class, 'room_id');
    }

    /**
     * 退房记录所属住户
     */
    public function resident() {
        return $this->belongsTo(Residentmodel::class, 'resident_id');
    }

    /**
     * 退房记录所属住户
     */
    public function store() {
        return $this->belongsTo(Storemodel::class, 'store_id');
    }

    /**
     * 处理该退房的员工
     */
    public function employee() {
        return $this->belongsTo(Employeemodel::class, 'employee_id');
    }

    /**
     * 退房的任务流
     */
    public function taskflow()
    {
        return $this->belongsTo(Taskflowmodel::class,'taskflow_id');
    }

    /**
     * 验房照片
     */
    public function check_images()
    {
        return $this->hasMany(Checkoutimagemodel::class,'checkout_id');
    }


    /**
     * new--checkout
     */


    /*
     * @param $room
     * @param $resident
     * @param $orders
     * @return array
     * 根据上传的类型已经水电读数计算一个初始的退款
     */
    public static function calcInitRefundMoney($checkout_type,$room,$resident,$orders,$handle_time,$utility_data,$employee)
    {
        // 按读数计算应收的水电费账单
        $utility_order  = Checkoutmodel::calcChargeUtilityMoney($utility_data,$employee);
        //计算一下初始的金额返回给前端
        $init_order     = Checkoutmodel::calcInitMoney($checkout_type,$room,$resident,$orders,$handle_time,$employee);
        //merge
        $charge_order   = array_merge($utility_order,$init_order['charge_order']);
        $spend_order    = $init_order['spend_order'];
        $charge_sum     = 0;
        foreach ($charge_order as $item) {
            $charge_sum += $item['money'];
        }
        $spend_sum    = 0;
        foreach ($spend_order as $item) {
            $spend_sum += $item['paid'];
        }
        $refund_sum = $spend_sum-$charge_sum;
        return compact('charge_order','spend_order','charge_sum','spend_sum','refund_sum');
    }


    /**
     * 按读数计算应收的水电费账单
     */
    private static function calcChargeUtilityMoney($utility_data,$employee)
    {
        $CI = &get_instance();
        if (empty($utility_data)) {
            return [];
        } else {
            $CI->load->model('meterreadingtransfermodel');
            foreach ($utility_data as $utility_data_each) {
                $time   = Carbon::parse($utility_data_each['time']);


            }
        }
    }

    /**
     * 计算初始金额
     */
    private static function calcInitMoney($type,$room,$resident,$orders,$handle_time,$employee)
    {
        //计算应缴
        $chargeOrders   = Checkoutmodel::calcChargeMoney($type,$room,$resident,$orders,$handle_time,$employee);
        //计算已交
        $spendOrders    = Checkoutmodel::calcSpendMoney($type,$room,$resident,$orders,$handle_time);
        return ['charge_order'=>$chargeOrders,'spend_order'=>$spendOrders];

    }

    /**
     * 补充某个日期内未生成的账单信息，返回array
     */
    private static function fillOrder($resident,$room,Carbon $time,$orders,$employee)
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
                        'uxid'          => $resident->id,
                        'employee_id'   => $employee->id,
                        'resident_id'   => $resident->id,
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
                        'uxid'          => $resident->id,
                        'employee_id'   => $employee->id,
                        'resident_id'   => $resident->id,
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
                        'uxid'          => $resident->id,
                        'employee_id'   => $employee->id,
                        'resident_id'   => $resident->id,
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
                        'uxid'          => $resident->id,
                        'employee_id'   => $employee->id,
                        'resident_id'   => $resident->id,
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
     * 计算退房时应收的金额
     */
    private static function calcChargeMoney($type,$room,$resident,$orders,$handle_time,$employee)
    {
        switch ($type) {
            case 'NORMAL_REFUND':
                $chargeOrders   = self::calcChargeNormalMoney($room,$resident,$orders,$handle_time,$employee);
                break;
            case 'UNDER_CONTRACT':
                $chargeOrders   = self::calcChargeUnderMoney($room,$resident,$orders,$handle_time,$employee);
                break;
            case 'NO_LIABILITY':
                $chargeOrders   = self::calcChargeNoMoney($room,$resident,$orders,$handle_time,$employee);
                break;
            default:
                $chargeOrders   = [];
                break;
        }
        return $chargeOrders;
    }

    /**
     * 正常退房时应收金额
     */
    private static function calcChargeNormalMoney($room,$resident,$orders,$handle_time,$employee)
    {
        //检查住户合同期内的账单是否已经全部生成
        $end_time   = $resident->end_time;
        //补充应该生成的账单
        $fillOrders = Checkoutmodel::fillOrder($resident,$room,$end_time,$orders,$employee);
        $pendingOrders  = $orders->where('status','PENDING')->toArray();
        $chargeOrder    = array_merge($fillOrders,$pendingOrders);
        return $chargeOrder;
    }

    /**
     * 计算违约退房应收金额
     */
    private static function calcChargeUnderMoney($room,$resident,$orders,$handle_time,$employee)
    {
        $now    = $handle_time;
        $order_end_time = $now->copy()->endOfMonth();
        //补到当月月底的账单
        $fillOrders = Checkoutmodel::fillOrder($resident,$room,$order_end_time,$orders,$employee);

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
                'uxid'          => $resident->id,
                'employee_id'   => $employee->id,
                'resident_id'   => $resident->id,
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
    private function calcChargeNoMoney($room,$resident,$orders,$handle_time,$employee)
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
                'uxid'          => $resident->id,
                'employee_id'   => $employee->id,
                'resident_id'   => $resident->id,
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
                'uxid'          => $resident->id,
                'employee_id'   => $employee->id,
                'resident_id'   => $resident->id,
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
                'uxid'          => $resident->id,
                'employee_id'   => $employee->id,
                'resident_id'   => $resident->id,
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
                'uxid'          => $resident->id,
                'employee_id'   => $employee->id,
                'resident_id'   => $resident->id,
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
                'uxid'          => $resident->id,
                'employee_id'   => $employee->id,
                'resident_id'   => $resident->id,
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
                'uxid'          => $resident->id,
                'employee_id'   => $employee->id,
                'resident_id'   => $resident->id,
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
     * 计算退房时应付的金额
     */
    private static function calcSpendMoney($type,$room,$resident,$orders,$handle_time)
    {
        switch ($type) {
            case 'NORMAL_REFUND':
                $spendOrders    = self::calcSpendNormalMoney($room,$resident,$orders,$handle_time);
                break;
            case 'UNDER_CONTRACT':
                $spendOrders    = self::calcSpendUnderMoney($orders,$handle_time);
                break;
            case 'NO_LIABILITY':
                $spendOrders    = self::calcSpendNoMoney($orders,$handle_time);
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
    private static function calcSpendUnderMoney($orders,$handle_time){
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
    private static function calcSpendNoMoney($orders,$handle_time){
        //已付金额是全部账单
        $spendOrders    = $orders->whereIn('status',['CONFIRM','COMPLATE'])->toArray();
        return $spendOrders;
    }

    /**
     * 计算正常退房时已交账单
     */
    private static function calcSpendNormalMoney($room,$resident,$orders,$handle_time)
    {
        $deposit    = $orders->whereIn('type',['DEPOSIT_R','DEPOSIT_O'])->toArray();
        $spendOrder = $deposit;
        return $spendOrder;
    }

    /**
     * 计算退租是应退金额
     */
    public static function calcRefundMoneyByRecord($record)
    {
        $resident   = $record->resident;
        $room       = $record->roomunion;
        $orders     = $resident->orders;
        $utility_data   = json_decode($record->utility_readings,true);
        $employee       = Employeemodel::find($record->employee_id);
        $refundMoney    = Checkoutmodel::calcInitRefundMoney($record->type,$room,$resident,$orders,$record->refund_time,$utility_data,$employee);
//        //计算退房添加的账单
        $create_orders  = self::calcCreateOrder(json_decode($record->add_orders),$room,$resident,$employee);
        //merge
        $charge_order   = array_merge($refundMoney['charge_order'],$create_orders);
        $spend_order    = $refundMoney['spend_order'];
        $charge_sum     = 0;
        foreach ($charge_order as $item) {
            $charge_sum += $item['money'];
        }
        $spend_sum    = 0;
        foreach ($spend_order as $item) {
            $spend_sum += $item['money'];
        }
        $refund_sum = $spend_sum-$charge_sum;
        return [
            'charge_order'  => $charge_order,
            'charge_init_order' => $refundMoney['charge_order'],
            'create_order'  => $create_orders,
            'spend_order'   => $spend_order,
            'charge_sum'    => $charge_sum,
            'spend_sum'     => $spend_sum,
            'refund_sum'    => $refund_sum
        ];
    }

    /**
     * 计算退房时添加的账单处理
     */
    public static function calcCreateOrder($create_orders,$room,$resident,$employee)
    {
        $orders = [];
        if (!empty($create_orders)) {
            foreach ($create_orders as $create_order){
                $handle_time    = Carbon::parse($create_order['time']);
                $order  = [
                    'number'=>Ordermodel::newNumber(),
                    'store_id'  => $resident->store_id,
                    'company_id'=> $resident->company_id,
                    'room_id'   => $resident->room_id,
                    'customer_id'   => $resident->customer_id,
                    'uxid'          => $resident->id,
                    'employee_id'   => $employee->id,
                    'resident_id'   => $resident->id,
                    'room_type_id'  => $room->room_type_id,
                    'money'     => $create_order['money'],
                    'paid'      => $create_order['money'],
                    'type'      => $create_order['type'] ,
                    'remark'    => $create_order['remark'] ,
                    'year'      => $handle_time->year,
                    'month'     => $handle_time->month,
                    'status'    => 'PENDING',
                    'pay_status'=> 'RENEWALS',
                    'begin_time'=> $handle_time->copy()->startOfMonth()->format('Y-m-d'),
                    'end_time'  => $handle_time->copy()->endOfMonth()->format('Y-m-d'),
                    'tag'       => 'CHECKOUT',
                ];
                $orders[]   = $order;
            }
        }
        return $orders;
    }

    /**
     * 处理退房的账单
     */
    public static function handleCheckoutOrder($record)
    {
        if($record!==Checkoutmodel::STATUS_UNPAID)
        {
            //...

            return false;
        }

        $resident   = $record->resident;
        $room       = $record->roomunion;
        $orders     = $resident->orders;
        //办理时间
        $handle_time        = $record->refund_time;
        $utility_readings   = $record->utility_readings;
        //生成水电账单
        if( !empty($utility_readings) ) {

//            $utility_readings   = json_decode($utility_readings,true);
//            $param_utility  = [];
//            foreach ($utility_readings as $utility_reading) {
//                $param_utility['room_id']   = $room->id;
//                $param_utility['$resident'] = $resident->id;
//                switch ($utility_reading['type']) {
//                    case 'COLDWATER':
//                        $param_utility['coldwater_reading'] = $utility_reading['coldwater_reading'];
//                        $param_utility['coldwater_image'] = $utility_reading['coldwater_image'];
//                        $param_utility['coldwater_time'] = $utility_reading['time'];
//                        break;
//                    case 'HOTWATER':
//                        $param_utility['hotwater_reading'] = $utility_reading['hotwater_reading'];
//                        $param_utility['hotwater_image'] = $utility_reading['hotwater_image'];
//                        $param_utility['hotwater_time'] = $utility_reading['time'];
//                        break;
//                    case 'ELECTRIC':
//                        $param_utility['electric_reading'] = $utility_reading['electric_reading'];
//                        $param_utility['electric_image'] = $utility_reading['electric_image'];
//                        $param_utility['electric_time'] = $utility_reading['time'];
//                        break;
////                    case 'GAS':
////                        break;
//                }
//            }
//            //这里需要调整
//            $utlity = $this->utility($param_utility);
//            $bills  = [];
//            if (isset($utlity['water'])){
//                $bills['WATER'] = $utlity['water'];
//            }
//            if (isset($utlity['hot_water'])){
//                $bills['HOT_WATER'] = $utlity['hot_water'];
//            }
//            if (isset($utlity['electric'])){
//                $bills['ELECTRICITY'] = $utlity['electric'];
//            }
//            $utility_orders = $this->createCheckoutUtilityOrder($record,$room,$resident,$bills);
//
//            Ordermodel::insert($utility_orders);
        }

        $employee   = Employeemodel::find($record->employee);
        //生成添加的账单
        if ( !empty($record->add_orders) ) {
            $add_orders = json_decode($record->add_orders);
            $crete_orders   = Checkoutmodel::calcCreateOrder($add_orders,$room,$resident,$employee);
            Ordermodel::insert($crete_orders);
        }
        //处理退房账单
        self::handleInitRefundMoney($record,$room,$resident,$orders,$handle_time,$employee);

    }

    /**
     * 处理退房的初始账单
     */
    private static function handleInitRefundMoney($record,$room,$resident,$orders,$handle_time,$employee)
    {
        $type   = $record->type;
        //处理应交
        self::handleChargeMoney($type,$room,$resident,$orders,$handle_time,$employee);
        //处理已交
        self::handleSpendMoney($type,$room,$resident,$orders,$handle_time);
    }

    /**
     * 处理应缴账单
     */
    private static function handleChargeMoney($type,$room,$resident,$orders,$handle_time,$employee)
    {
        switch ($type) {
            case 'NORMAL_REFUND':
                $chargeOrders   = self::handleChargeNormalMoney($room,$resident,$orders,$handle_time,$employee);
                break;
            case 'UNDER_CONTRACT':
                $chargeOrders   = self::handleChargeUnderMoney($room,$resident,$orders,$handle_time,$employee);
                break;
            case 'NO_LIABILITY':
                $chargeOrders   = self::handleChargeNoMoney($room,$resident,$orders,$handle_time,$employee);
                break;
            default:
                $chargeOrders   = false;
                break;
        }
    }

    private static function handleChargeNoMoney($room,$resident,$orders,$handle_time,$employee)
    {
        $orders = self::calcChargeNoMoney($room,$resident,$orders,$handle_time,$employee);
        Ordermodel::insert($orders);
    }

    /**
     * 处理违约退房的账单
     */
    private static function handleChargeUnderMoney($room,$resident,$orders,Carbon $handle_time,$employee)
    {
        $now    = $handle_time;
        $order_end_time = $now->copy()->endOfMonth();
        //补到当月月底的账单
        $fillOrders = self::fillOrder($resident,$room,$order_end_time,$orders,$employee);

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
                'uxid'          => $resident->id,
                'employee_id'   => $employee->id,
                'resident_id'   => $resident->id,
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
    private static function handleChargeNormalMoney($room,$resident,$orders,$handle_time,$employee)
    {
        //检查住户合同期内的账单是否已经全部生成
        $end_time   = $handle_time;
        //补充应该生成的账单
        $fillOrders = Checkoutmodel::fillOrder($resident,$room,$end_time,$orders,$employee);
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
     * 退房类型转换中文
     */
    public static function typeTransfer($type)
    {
        switch ($type) {
            case self::TYPE_NORMAL:
                $transfer   = '正常退房';
                break;
            case self::TYPE_ABNORMAL:
                $transfer   = '违约退房';
                break;
            case self::TYPE_NOLIABILITY:
                $transfer   = '免责退房';
                break;
            default:
                $transfer   = '';
        }
        return $transfer;
    }

}
