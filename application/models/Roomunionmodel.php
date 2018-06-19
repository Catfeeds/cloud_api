<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/20 0020
 * Time:        16:17
 * Describe:    BOSS
 * 房间信息表(集中式)
 */
class Roomunionmodel extends Basemodel{

    const HALF = 'HALF';    //合租
    const FULL = 'FULL';    //整租
    /**
     * 房间的状态
     */
    const STATE_BLANK       = 'BLANK';      // 空
    const STATE_RESERVE     = 'RESERVE';    // 预订
    const STATE_RENT        = 'RENT';       // 出租
    const STATE_ARREARS     = 'ARREARS';    // 欠费
    const STATE_REFUND      = 'REFUND';     // 退房
    const STATE_OTHER       = 'OTHER';      // 其他 保留
    const STATE_OCCUPIED    = 'OCCUPIED';   // 房间被占用的状态, 可能是预约, 或者是办理入住后订单未确认之间的状态

    protected $table    = 'boss_room_union';

    protected $fillable = [
        'area',
        'layer',
        'number',
        'status',
        'end_time',
        'device_id',
        'begin_time',
        'rent_price',
        'resident_id',
        'people_count',
        'apartment_id',
        'room_type_id',
        'property_price',
        'contract_template_short_id',
        'contract_template_long_id',
        'contract_template_reserve_id',
    ];

    protected $hidden   = ['created_at','updated_at','deleted_at'];

    //房型展示
    public function room_type(){

        return $this->belongsTo(Roomtypemodel::class,'room_type_id')
                            ->select('id','name','room_number','hall_number','toilet_number');
    }

    //房型展示
    public function roomtype(){

        return $this->belongsTo(Roomtypemodel::class,'room_type_id');
    }

    //房间住户信息
    public function resident(){

        return $this->belongsTo(Residentmodel::class,'resident_id');
    }

    public function residents(){

        return $this->belongsTo(Residentmodel::class,'resident_id')->select('id');
    }
    //房间所属门店信息
    public function store(){

        return $this->belongsTo(Storemodel::class,'store_id');
    }
    public function store_s(){

        return $this->belongsTo(Storemodel::class,'store_id')->select('id','name');
    }

    //房间所属楼栋信息
    public function building(){

        return $this->belongsTo(Buildingmodel::class,'building_id');
    }
    public function building_s(){

        return $this->belongsTo(Buildingmodel::class,'building_id')->select('id','name');
    }

    //房间的长租合同模板
    public function long_template(){
        return $this->belongsTo(Contracttemplatemodel::class,'contract_template_long_id')
            ->where('rent_type','LONG')->select(['id','name']);
    }
    //房间的短租合同模板
    public function short_template(){
        return $this->belongsTo(Contracttemplatemodel::class,'contract_template_short_id')
            ->where('rent_type','SHORT')->select(['id','name']);
    }
    //房间的预定合同模板
    public function reserve_template(){
        return $this->belongsTo(Contracttemplatemodel::class,'contract_template_reserve_id')
            ->where('rent_type','RESERVE')->select(['id','name']);
    }

    public function orders()
    {
        return $this->hasMany(Ordermodel::class,'room_id');
    }

    public function order()
    {
        return $this->hasMany(Ordermodel::class,'room_id')
                    ->where('type','ROOM')->whereIn('status',['GENERATE','AUDITED','PENDING'])
                    ->select('id','room_id','resident_id','type','status');
    }

//    public function utilities()
//    {
//        return $this->hasMany(Utilitymodel::class, 'room_id');
//    }

    public function devices()
    {
        return $this->hasMany(Devicemodel::class, 'room_id');
    }

//    //房屋公共智能设备
//    public function housesmartdevice(){
//
//        return $this->belongsTo(Smartdevicemodel::class,'house_smart_device_id');
//    }
//
//    //房间的智能设备
//    public function smartdevice(){
//
//        return $this->belongsTo(SmartDevicemodel::class,'smart_device_id');
//    }

    //合租人信息
//    public function unionresident(){
//
//        return $this->hasMany(Unionresidentmodel::class,'room_id');
//    }

    /**
     * 是否空闲
     */
    public function isBlank(){
        if($this->status==self::STATE_BLANK){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 把房间状态更新为占用
     */
    public function Occupie(){
        //$this->status   = self::OCCUPIED;
        return $this->update(['status'=>self::STATE_OCCUPIED]);
    }
    /**
     * 把房间状态更新为空闲
     */
    public function Blank(){
        //$this->status   = self::BLANK;
        return $this->update(['status'=>self::STATE_BLANK]);
    }


    /*
     * 查询
     */
    public function room_details($where,$filed,$time){
        $arrears_count = 0;
        $this->details = Roomunionmodel::with('room_type')->with('resident')->with('order')
                        ->where($where)->whereBetween('updated_at',$time)
                        ->get($filed)->groupBy('layer')
                        ->map(function ($s){
                            $s = $s->toArray();
                            global $arrears_count;
//                            $s['count']= 0;
//                            foreach ($s as $key=>$value){
//                                if (!empty($s[$key]['order'])){
//                                    $s['count'] += 1;
//                                }
//                            }
//                            $arrears_count += $s['count'];
                            $count=0;
                            foreach ($s as $key=>$value){
                                if (!empty($s[$key]['order'])){
                                    $count += 1;
                                }
                            }
                            $arrears_count += $count;
                            return [$s,'arrears_count'=>$arrears_count];
                        })->toArray();

        //var_dump($this->details);
        if (!empty($where['status'])){unset($where['status']);}
        //var_dump($where);
        $this->total_count    = Roomunionmodel::where($where)->whereBetween('updated_at',$time)->get($filed)->count();
        $this->blank_count    = Roomunionmodel::where($where)->where('status','BLANK')->whereBetween('updated_at',$time)->get($filed)->count();
        $this->reserve_count  = Roomunionmodel::where($where)->where('status','RESERVE')->whereBetween('updated_at',$time)->get($filed)->count();
        $this->rent_count     = Roomunionmodel::where($where)->where('status','RENT')->whereBetween('updated_at',$time)->get($filed)->count();
        foreach ($this->details as $key=>$value){

            if(isset(($this->details)[$key]['arrears_count'])){
                $arrears_count = ($this->details)[$key]['arrears_count'];
            }

        }
        $this->arrears_count  = $arrears_count;
        return $this;
    }

    /**
     * 取消办理, 房间状态置空
     */
    public function resetRoom($roomId)
    {
        $room   = Room::find($roomId);

        if (!$room) {
            throw new \Exception('未找到该房间');
        }

        $room->update([
            'status'        => Roomunionmodel::STATE_BLANK,
            'people_count'  => 0,
            'resident_id'   => 0,
        ]);

        return true;
    }
}