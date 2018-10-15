<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;

/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/24 0024
 * Time:        16:47
 * Describe:    优惠券
 */
class Couponmodel extends Basemodel {
    /**
     * 用户优惠券的状态
     */
    const STATUS_ASSIGNED    = 'ASSIGNED'; //已分配的状态, 适用于吸粉活动的优惠券
    const STATUS_UNUSED      = 'UNUSED'; //未使用
    const STATUS_ROLLBACKING = 'ROLLBACKING'; //订单取消之后的优惠券回滚状态
    const STATUS_OCCUPIED    = 'OCCUPIED'; //订单支付且未确认时优惠券的占用状态
    const STATUS_USED        = 'USED'; //已使用
    const STATUS_EXPIRED     = 'EXPIRED'; //未使用且过期
    protected $table         = 'boss_coupon';

    protected $fillable = [
        'status',
        'deadline',
        'activity_id',
        'customer_id',
        'resident_id',
        'order_id',
        'coupon_type_id',
    ];

    /**
     * 优惠券所属的活动
     */
    public function activity() {
        return $this->belongsTo(Activitymodel::class, 'activity_id');
    }

    /**
     * 所属的优惠券模板
     */
    public function coupon_type() {
        return $this->belongsTo(Coupontypemodel::class, 'coupon_type_id');
    }
    /**
     * [确认订单时, 将优惠券给销掉]
     * @param  array  $orderIds [订单的id数组]
     * @return [type]           [操作结果]
     */
    public function invalidByOrders(array $orderIds) {
        return Couponmodel::whereIn('order_id', $orderIds)->update([
            'status' => Couponmodel::STATUS_USED,
        ]);
    }

    /**
     * [查找订单可用的优惠券]
     * @param  [ResidentEntity]     $resident        [住户记录的实例]
     * @param  [OrderCollection]    $orderCollection [订单的集合]
     *
     * @return [Array]              [优惠券组成的数组]
     */
    public function queryByOrders($resident, $orderCollection) {
        $orders = $orderCollection->groupBy('type');
        //优惠券的使用目前仅限于房租和代金券
        if (!isset($orders[Ordermodel::PAYTYPE_ROOM]) && !isset($orders[Ordermodel::PAYTYPE_MANAGEMENT])) {
            return NULL;
        }

        //月付用户首次支付不能使用优惠券
        if (1 == $resident->pay_frequency) {
            $tmpOrder = $orderCollection->first();
            if (Ordermodel::PAYSTATE_PAYMENT == $tmpOrder->pay_status AND Carbon::parse($resident->begin_time)->day < 21) {
                return NULL;
            }
        }

        //之前是查找该住户下的优惠券，现在改成查找用户的优惠券
        //        $couopnCollection   = $resident->customer->coupons()->where('status', Couponmodel::STATUS_UNUSED)->get();
        $couopnCollection = $resident->coupons()->where('status', Couponmodel::STATUS_UNUSED)->get();
        $usageList        = $couopnCollection->groupBy('coupon_type.limit');

        //找出房租可用的代金券
        $forRent = $this
            ->getCouponByUsage(
                $resident,
                $orders,
                $usageList,
                Ordermodel::PAYTYPE_ROOM,
                $resident->real_rent_money
            );

        //找出物业服务费可用的代金券
        $forService = $this
            ->getCouponByUsage(
                $resident,
                $orders,
                $usageList,
                Ordermodel::PAYTYPE_MANAGEMENT,
                $resident->real_property_costs
            );

        if ($forRent) {
            foreach ($forRent as $coupon) {
                $coupons[] = $coupon;
            }
        }

        if ($forService) {
            foreach ($forService as $coupon) {
                $coupons[] = $coupon;
            }
        }

        return isset($coupons) ? $coupons : NULL;
    }

    /**
     * [根据优惠券的类型挑选优惠券]
     *
     * @param  [ResidentEntity]     $resident  [Resident 实例]
     * @param  [OrderCollection]    $orders    [Order的collection,通过type进行groupBy得到的]
     * @param  [CouponCollection]   $usageList [通过coupon_type.limit进行groupBy得到的Coupon的Collection]
     * @param  [string]             $typeName  [订单的类型, 也是coupon_type的limit值]
     * @param  [string/float]       $price     [该笔账单的金额]
     *
     * @return [array] [Coupon信息的数组]
     */
    private function getCouponByUsage($resident, $orders, $usageList, $typeName, $price) {
        if (!isset($orders[$typeName]) OR !isset($usageList[$typeName])) {
            return NULL;
        }

        //每条订单只允许使用一张优惠券, 因此优惠券数量最多于该类型订单数量一样
        $list = $usageList[$typeName]->take(count($orders[$typeName]));

        foreach ($list as $coupon) {
            $couponType = $coupon->coupon_type;
            $coupons[]  = array(
                'id'       => $coupon->id,
                'usage'    => $typeName,
                'name'     => $couponType->name,
                'deadline' => Carbon::parse($coupon->deadline)->toDateString(),
                'discount' => $this->calcDiscount($price, $coupon),
            );
        }
        return $coupons;
    }

    /**
     * [根据优惠券类型的不同, 计算出相应的价格]
     *
     * @param  [float]              $price      [订单中的价格]
     * @param  [CouponEntity]       $coupon     [优惠券实例]
     * @return [float]                          [该优惠券可以产生的最大折扣金额]
     */
    private function calcDiscount($price, $coupon) {
        $couponType = $coupon->coupon_type;

        switch ($couponType->type) {
        case Coupontypemodel::TYPE_CASH:
            $discount = $couponType->discount;
            break;
        case Coupontypemodel::TYPE_DISCOUNT:
            $discount = $price * (100 - $couponType->discount) / 100.0;
            break;
        case Coupontypemodel::TYPE_REMIT:
            $discount = $price;
            break;
        default:
            $discount = 0;
            break;
        }

        return $discount;
    }

    /**
     * [计算使用优惠券可以产生的优惠金额]
     * @param  [ResidentEntity]     $resident   [住户实例]
     * @param  [orderCollection]    $orders     [订单集合]
     * @param  [couponCollection]   $coupons    [优惠券集合]
     * @param  [bool]               $update     [是否更新记录]
     *
     * @return [float]              [优惠的总金额]
     */
    public function bindOrdersAndCalcDiscount($resident, $orders, $coupons, $update = false) {
        if (empty($coupons)) {
            return 0;
        }

        $orders  = $orders->groupBy('type');
        $coupons = $coupons->groupBy('coupon_type.limit');

        $discount   = 0;
        $rentOrders = $orders->pull(Ordermodel::PAYTYPE_ROOM);

        if (count($rentOrders)) {
            $discount += $this->calcDiscountByType(
                $resident,
                $rentOrders,
                $coupons,
                Ordermodel::PAYTYPE_ROOM,
                $resident->real_rent_money,
                $update
            );
        }

        $managementOrders = $orders->pull(Ordermodel::PAYTYPE_MANAGEMENT);

        if (count($managementOrders)) {
            $discount += $this->calcDiscountByType(
                $resident,
                $managementOrders,
                $coupons,
                Ordermodel::PAYTYPE_MANAGEMENT,
                $resident->real_property_costs,
                $update
            );
        }

        return $discount;
    }

    /**
     * 计算通过使用优惠券可以获得的优惠
     */
    private function calcDiscountByType($resident, $orderCollection, $coupons, $typeName, $price, $update = false) {
        if (!isset($coupons[$typeName])) {
            return 0;
        }

        //将订单按照金额排序, 降序
        $orderCollection = $orderCollection->sortByDesc('money');
        $order           = $orderCollection->first();

        //月付用户, 首次支付不能使用优惠券
        if (1 == $resident->pay_frequency && Ordermodel::PAYSTATE_PAYMENT == $order->pay_status && count($orderCollection) == 1) {
            return 0;
        }

        //对优惠券做出的限制, 优惠券和订单是一对一绑定的, 就是说, 一张优惠券只能用于一条订单, 一条订单只能使用一张优惠券.
        //代金券可以抵扣的金额不能大于订单本身的金额
        $discount = 0;

        //遍历优惠券列表, 计算优惠金额
        foreach ($coupons[$typeName] as $coupon) {
            $couponType = $coupon->coupon_type;
            $order      = $orderCollection->shift();

            switch ($couponType->type) {
            case Coupontypemodel::TYPE_CASH:
                $deduction = min($order->money, $couponType->discount);
                break;
            case Coupontypemodel::TYPE_DISCOUNT:
                $deduction = min($order->money, $price * (100 - $couponType->discount) / 100.0);
                break;
            case Coupontypemodel::TYPE_REMIT:
                $deduction = min($price, $order->money);
                break;
            default:
                $deduction = 0;
                break;
            }

            $discount += $deduction;

            if (!$update) {
                continue;
            }

            $coupon->update([
                'order_id' => $order->id,
                'status'   => Couponmodel::STATUS_USED,
            ]);

            $order->update([
                'paid'   => max(0, $order->money - $deduction),
                'remark' => $order->remark ? $order->remark . "-使用优惠券优惠{$deduction}" : "使用优惠券优惠{$deduction}",
            ]);
        }

        return $discount;
    }

    public function resident(){
        return $this->belongsTo(Residentmodel::class, 'resident_id');
    }
  //优惠券绑定住户
    public function bindCoupon($resident_id)
    {
        $customer = Residentmodel::where('id', $resident_id)->select(['customer_id'])->first();
        if(!$customer){
            log_message('debug','用户: '.$resident_id.'未找到');
            return;
        }

        $coupon = Couponmodel::where('status', Couponmodel::STATUS_UNUSED)->where('customer_id', $customer->customer_id);
        if(!$coupon){
            log_message('debug','用户: '.$customer.'无可绑定的优惠券');
            return;
        }

        $bindResident = $coupon->update(['resident_id' => $resident_id]);
        if(!$bindResident){
            log_message('debug','住户：'.$resident_id.'绑定优惠券失败');
            return;
        }

        return true;
    }
    
    public function employee(){
        return $this->belongsTo(Employeemodel::class, 'employee_id')->select(['id', 'name']);
    }
}
