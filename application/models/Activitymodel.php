<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/25 0025
 * Time:        9:45
 * Describe:
 */
class Activitymodel extends Basemodel {
    public function __construct(array $attributes = []) {
        parent::__construct($attributes);
    }

    protected $table = 'boss_activity';

    protected $fillable = ['name', 'description'];

    protected $dates = ['created_at', 'updated_at', 'start_time', 'end_time'];

    /**
     * 活动类型
     */
    const TYPE_ATTRACT  = 'ATTRACT'; //吸粉活动
    const TYPE_NORMAL   = 'NORMAL'; //普通活动, 先不管, 先处理吸粉活动
    const TYPE_DISCOUNT = 'DISCOUNT'; //房租打折

    /**
     * 获取该活动相关的优惠券类型
     */
    public function coupontypes() {
        return $this
            ->belongsToMany(
                Coupontypemodel::class, 'boss_activity_coupontype', 'activity_id', 'coupon_type_id'
            )
            ->withPivot('count', 'min');
    }

    public function coupons() {
        return $this->hasMany(Couponmodel::class, 'activity_id');
    }

    /**
     * 所有活动的类型
     */
    public function getAllTypes() {
        return [
            Activitymodel::TYPE_ATTRACT,
            Activitymodel::TYPE_NORMAL,
            Activitymodel::TYPE_DISCOUNT,
        ];
    }
    public function store() {
        return $this->belongsTo(Storeactivitymodel::class, 'id', 'activity_id');
    }

    public function sendCheckIn($resident_id,$time) {
        $resident = Residentmodel::where('id', $resident_id)->first();
        $store_id = $resident->store_id;
        $activity_id = Activitymodel::where('activity_type','CHECKIN')
            ->where('start_time','<=',Carbon::now())
            ->where('end_time','>=',Carbon::now())
            ->where('type','!=','LOWER')
            ->where(function($query) use ($store_id){
                $query->orwherehas('store',function($query) use ($store_id){
                    $query->where('store_id',$store_id);
                });
            })->select(['id','prize_id','end_time','start_time'])->first();
        if(!$activity_id){
            return '没有查询到该活动';
        }
        $ac_prize = Activityprizemodel::where('id',$activity_id->prize_id)->select(['prize','count','grant'])->first();
        $prize = unserialize($ac_prize->prize);
        $count = unserialize($ac_prize->count);
        $grant = unserialize($ac_prize->grant);
        $coupon = Couponmodel::where('customer_id',$resident->customer_id)->whereIn('coupon_type_id',$prize)->where('activity_id',$activity_id->id)->count();
        if($coupon>=1){
            return '以从该活动领取过同类奖品';
        }
        switch ($time >= 3 ? ($time >= 6 ? ($time >= 12 ? 4 : 3) : 2) : 1) {
            case  4:
                $time = 'A_year';
                break;
            case  3:
                $time = 'Half_A_year';
                break;
            case  2:
                $time = 'Three_months';
                break;
            case  1:
                $time = 'under_time';
                break;
        }
        if($time=='Three_months'){
            $prize_id = $prize['one'];
            $count['one'] = $count['one'] - $grant['one'];
            $grant_number = $grant['one'];
        }elseif($time=='Half_A_year'){
            $prize_id = $prize['two'];
            $count['two'] = $count['two'] - $grant['two'];
            $grant_number = $grant['two'];
        }elseif($time=='A_year'){
            $prize_id = $prize['three'];
            $count['three'] = $count['three'] - $grant['three'];
            $grant_number = $grant['three'];
        }elseif($time == 'under_time'){
            return '入住时间不满足活动需求';
        }
        if(($count['one']<0) || ($count['two']<0) || ($count['three']<0)){
            return '您来晚了，奖品发放完了';
        }
        $count_change = Activityprizemodel::find($activity_id->prize_id);
        $count_change ->count=serialize($count);
        if(!$count_change->save()){
            return '奖品数量更改出错';
        }
        $datetime = time();
        $coupon_type = Coupontypemodel::where('id',$prize_id)->select(['deadline'])->first();
        for($i=0;$i<$grant_number;$i++){
            $data[] =[
                'customer_id'    => $resident->customer_id,
                'resident_id'    => $resident->id,
                'activity_id'    => $activity_id->id,
                'coupon_type_id' => $prize_id,
                'store_ids'       => $store_id,
                'status'         => 'UNUSED',
                'deadline'       => $coupon_type->deadline,
                'created_at'     => $datetime,
                'updated_at'     => $datetime,
            ];
        }
        Couponmodel::insert($data);
        return '发放成功';
    }


    /*
     * 老带新优惠卷
     * */
    public function sendOldbeltNew($old_phone) {
        $old_id = Residentmodel::where('phone',$old_phone)->select()->first();
        if(!$old_id){
            return '没有查询到该老用户';
        }
        $store_id = $old_id->store_id;
        $activity_id = Activitymodel::where('activity_type','OLDBELTNEW')
            ->where('start_time','<=',Carbon::now())
            ->where('end_time','>=',Carbon::now())
            ->where('type','!=','LOWER')
            ->where(function($query) use ($store_id){
                $query->orwherehas('store',function($query) use ($store_id){
                    $query->where('store_id',$store_id);
                });
            })->select(['id','prize_id'])->first();
        if(!$activity_id){
            return '没有查询到该活动';
        }

        $ac_prize = Activityprizemodel::where('id',$activity_id->prize_id)->select(['prize','count','grant'])->first();
        $prize = unserialize($ac_prize->prize);
        $count = unserialize($ac_prize->count);
        $grant = unserialize($ac_prize->grant);
        $count['old'] = $count['old'] - $grant['old'];
        $datetime = time();
        $coupon_type = Coupontypemodel::where('id',$prize['old'])->select(['deadline'])->first();
        $count_change = Activityprizemodel::find($activity_id->prize_id);
        if($count['old']<0){
            return '您来晚了，奖品发放完了';
        }else{
            for($i=0;$i<$grant['old'];$i++){
            $old[] =[
                'customer_id'    => $old_id->customer_id,
                'resident_id'    => $old_id->id,
                'activity_id'    => $activity_id->id,
                'coupon_type_id' => $prize['old'],
                'store_ids'      => $store_id,
                'status'         => 'UNUSED',
                'deadline'       => $coupon_type->deadline,
                'created_at'     => $datetime,
                'updated_at'     => $datetime,
             ];
            }
            Couponmodel::insert($old);
            $count_change ->count=serialize($count);
            if(!$count_change->save()){
                return '奖品数量更改出错';
            }
        }
        return '发放成功';
    }
}
