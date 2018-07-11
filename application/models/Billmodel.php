<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/9 0009
 * Time:        9:40
 * Describe:
 */
class Billmodel extends Basemodel
{
    protected $table    = 'boss_bill';

    protected $casts    = ['data'=>'array'];

    public function roomunion()
    {

        return $this->belongsTo(Roomunionmodel::class,'room_id');
    }
    public function roomunion_s()
    {

        return $this->belongsTo(Roomunionmodel::class,'room_id')
            ->select('id','number');
    }


    public function store()
    {

        return $this->belongsTo(Storemodel::class,'store_id');
    }
    public function store_s()
    {

        return $this->belongsTo(Storemodel::class,'store_id')
            ->select('id','name');
    }

    public function resident()
    {

        return $this->belongsTo(Residentmodel::class,'resident_id');
    }
    public function resident_s()
    {

        return $this->belongsTo(Residentmodel::class,'resident_id')
            ->select('id','name');
    }

    public function employee()
    {
        return $this->belongsTo(Employeemodel::class,'employee_id');
    }
    public function employee_s()
    {
        return $this->belongsTo(Employeemodel::class,'employee_id')
            ->select('id','name');
    }

    public function order()
    {
        return $this->hasMany(Ordermodel::class,'sequence_number','sequence_number')
                ->select('id','sequence_number','paid','type','year','month');
    }

    /**
     * 检索当日确定的账单的数量
     */
    public function ordersConfirmedToday()
    {
        return Billmodel::whereDate('updated_at', '=', date('Y-m-d'))
            ->count();
    }
}
