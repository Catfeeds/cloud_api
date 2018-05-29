<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/14 0014
 * Time:        15:07
 * Describe:
 */
class Ordermodel extends Basemodel{

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
     * 支付类型
     */
    const PAYTYPE_ROOM          = 'ROOM';           // 租房
    const PAYTYPE_CLEAN         = 'CLEAN';          // 清洁服务费
    const PAYTYPE_DEIVCE        = 'DEIVCE';         // 设备
    const PAYTYPE_DEVICE        = 'DEIVCE';         // 设备
    const PAYTYPE_UTILITY       = 'UTILITY';        // 水电费
    const PAYTYPE_REFUND        = 'REFUND';         // 退房
    const PAYTYPE_RESERVE       = 'RESERVE';        // 预订
    const PAYTYPE_MANAGEMENT    = 'MANAGEMENT';     // 物业服务费
    const PAYTYPE_DEPOSIT_R     = 'DEPOSIT_R';      // 房租押金
    const PAYTYPE_DEPOSIT_O     = 'DEPOSIT_O';      // 其他押金
    const PAYTYPE_OTHER         = 'OTHER';          // 其他收费
    const PAYTYPE_ELECTRIC      = 'ELECTRICITY';    // 电费
    const PAYTYPE_WATER         = 'WATER';          // 水费
    const PAYTYPE_WATER_HOT     = 'HOT_WATER';      // 热水水费
    const PAYTYPE_REPAIR        = 'REPAIR';         // 物品维修费
    const PAYTYPE_COMPENSATION  = 'COMPENSATION';   // 物品赔偿费
    const PAYTYPE_OVERDUE       = 'OVERDUE';        // 滞纳金

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

    /**
     * 所有的订单类型
     */
    public static function allTypes()
    {
        return [
            self::PAYTYPE_ROOM,
            self::PAYTYPE_DEVICE,
            self::PAYTYPE_UTILITY,
            self::PAYTYPE_REFUND,
            self::PAYTYPE_RESERVE,
            self::PAYTYPE_MANAGEMENT,
            self::PAYTYPE_DEPOSIT_R,
            self::PAYTYPE_DEPOSIT_O,
            self::PAYTYPE_OTHER,
            self::PAYTYPE_ELECTRIC,
            self::PAYTYPE_WATER,
            self::PAYTYPE_WATER_HOT,
            self::PAYTYPE_CLEAN,
            self::PAYTYPE_COMPENSATION,
            self::PAYTYPE_OVERDUE,
            self::PAYTYPE_REPAIR,
        ];
    }

    /**
     * 判断账单是否是水电账单
     */
    public static function isUtilityBill($orderType)
    {
        return in_array($orderType, [
            self::PAYTYPE_WATER,
            self::PAYTYPE_WATER_HOT,
            self::PAYTYPE_ELECTRIC,
        ]);
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
            self::PAYTYPE_ELECTRIC      => '用电服务费',
            self::PAYTYPE_WATER         => '冷水服务费',
            self::PAYTYPE_WATER_HOT     => '热水服务费',
            self::PAYTYPE_CLEAN         => '清洁服务费',
            self::PAYTYPE_COMPENSATION  => '物品赔偿费',
            self::PAYTYPE_OVERDUE       => '滞纳金',
            self::PAYTYPE_REPAIR        => '维修服务费',
        ];
    }


    public static function typeUnit($type)
    {
        $units = [
            self::PAYTYPE_WATER => '立方',
            self::PAYTYPE_WATER_HOT => '立方',
            self::PAYTYPE_ELECTRIC => '度',
        ];

        if (!isset($units[$type])) {
            return '未知';
        }

        return $units[$type];
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
     * 订单仍可以修改的状态
     */
    public static function unpaidStatuses()
    {
        return [
            self::STATE_GENERATED,
            self::STATE_AUDITED,
            self::STATE_PENDING,
        ];
    }

    /**
     * 订单已经支付的状态
     */
    public static function paidStatuses()
    {
        return [
            self::STATE_CONFIRM,
            self::STATE_COMPLETED,
        ];
    }

    /**
     * 生成新的订单编号
     */
    public static function newNumber($cityAbbreviation = '', $apartmentAbbreviation = '')
    {
        return strtoupper($cityAbbreviation) . strtoupper($apartmentAbbreviation) . date('YmdHis') . mt_rand(1000, 9999);
    }

    /**
     * 订单的状态名称
     */
    public static function getStatusName($status = null)
    {
        $names = [
            self::STATE_GENERATED   => '未审核',
            self::STATE_AUDITED     => '已审核',
            self::STATE_PENDING     => '未支付',
            self::STATE_CONFIRM     => '已支付',
            self::STATE_COMPLETED   => '已完成',
            self::STATE_REFUND      => '已退款',
            self::STATE_EXPIRE      => '已超时',
            self::STATE_CLOSE       => '已关闭',
        ];

        return isset($names[$status]) ? $names[$status] : '未知';
    }


    /**
     * 生成随机数作为订单编号
     */
    public function getOrderNumber()
    {
        return date('YmdHis').mt_rand(1000000000, intval(9999999999));
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
     * 确定一笔订单的支付截止日
     * 对于批量生成的账单，截止日是该账单月的前几天，比如 10 日
     * 对于单独添加的账单，对比一下
     */
    public static function calcPayDate($billYear, $billMonth)
    {
        $now            = Carbon::now();
        $thisYear       = $now->year;
        $thisMonth      = $now->month;
        $bufferDayCount = config_item('bill_pay_buffer') - 1;

        //如果是当月及之前的账单，截止日为当前日期加上缓冲期
        if ($billYear < $thisYear || $thisYear == $billYear && $billMonth <= $thisMonth) {
            return $now->copy()->addDays($bufferDayCount);
        }

        //如果是次月及以后的账单，截止日到次月的缓冲期结束
        return $now->startOfMonth()->addMonth()->startOfMonth()->addDays($bufferDayCount);
    }
}
