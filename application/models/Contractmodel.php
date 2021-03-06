<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/20 0020
 * Time:        16:07
 * Describe:    BOSS
 * 合同表
 */
class Contractmodel extends Basemodel {

    protected $table = 'boss_contract';

    protected $hidden = ['deleted_at'];

    /**
     * 合同的类型
     */
    const RENT_LONG     = 'LONG';
    const RENT_SHORT    = 'SHORT';
    const RENT_RESERVE  = 'RESERVE';

    /**
     * 签署状态
     */
    const STATUS_GENERATED = 'GENERATED'; // 未签署
    const STATUS_SIGNING   = 'SIGNING'; // 签署中
    const STATUS_ARCHIVED  = 'ARCHIVED'; // 已存档

    //签署人
    public function resident() {
        return $this->belongsTo(Residentmodel::class, 'resident_id');
    }

    public function emp() {
        return $this->hasManyThrough(Employeemodel::class, Residentmodel::class, 'resident_id', 'employee_id')->select('id', 'name');
    }

    //经办人
    public function employee() {
        return $this->belongsTo(Employeemodel::class, 'employee_id');
    }
    //门店城市 店名
    public function store() {
        return $this->belongsTo(Storemodel::class, 'store_id');
    }
    //建筑名 房号
    public function roomunion() {
        return $this->belongsTo(Roomunionmodel::class, 'room_id')->select('id', 'building_name', 'number', 'status');
    }

    //房间id号
    public function room() {
        return $this->belongsTo(Roomunionmodel::class, 'room_id')->select('id', 'number');
    }

    // 合约信息
    public function residents() {
        return $this->belongsTo(Residentmodel::class, 'resident_id');
    }

    /**
     * 合同的签署交易记录
     */
    public function transactions() {
        return $this->hasMany(Fddrecordmodel::class, 'contract_id');
    }

    //预定人信息
    public function bookresident() {
        return $this->belongsTo(Residentmodel::class, 'resident_id')->select('id', 'name', 'begin_time', 'book_money');
    }

    //预定人信息 入住时间 定金
    public function booking() {
        return $this->belongsTo(Residentmodel::class, 'resident_id')->select('id', 'begin_time', 'book_money');
    }
    //合同数量：用于查看所有住户入住流程中签署的预定和入住合同
    public function count($store_ids, $residents ,$where = [] , $search = ''){
        $count = Contractmodel::where($where)
                ->where(function ($query) use ($search) {
                    $query->orWhereHas('resident', function ($query) use ($search) {
                        $query->where('name', 'like', "%$search%");
                    })->orWhereHas('roomunion', function ($query) use ($search) {
                        $query->where('number', 'like', "%$search%");
                    });
                })
                ->whereIn('store_id', $store_ids)
                ->whereIn('resident_id', $residents)
                ->count();
        return $count;
    }
}
