<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/7/30 0030
 * Time:        11:01
 * Describe:
 */
class Taskflowmodel extends Basemodel
{
    const STATE_AUDIT   = 'AUDIT';
    const STATE_APPROVED= 'APPROVED';
    const STATE_UNAPPROVED= 'UNAPPROVED';
    const STATE_CLOSED  = 'CLOSED';
    const TYPE_CHECKOUT = 'CHECKOUT';
    const CREATE_EMPLOYEE   = 'EMPLOYEE';
    const CREATE_CUSTOMER   = 'CUSTOMER';

    protected $table    = 'boss_taskflow';

    protected $fillable = [
        'company_id','name','type','description'
    ];

    /**
     * 发起员工
     */
    public function employee()
    {
        return $this->belongsTo(Employeemodel::class,'employee_id');
    }

    /**
     * 最近操作的步骤
     */
    public function step()
    {
        return $this->belongsTo(Taskflowstepmodel::class,'step_id');
    }

    /**
     * 审核步骤
     */
    public function steps()
    {
        return $this->hasMany(Taskflowstepmodel::class,'taskflow_id');
    }

    /**
     * 审核记录
     */
    public function record()
    {
        return $this->hasMany(Taskflowrecordmodel::class,'taskflow_id');
    }

    /**
     * 退房的信息
     */
    public function checkout()
    {
        return $this->hasOne(Checkoutmodel::class,'taskflow_id');
    }

    /**
     * 门店
     */
    public function store()
    {
        return $this->belongsTo(Storemodel::class,'store_id');
    }
    /**
     * 生成审批编号
     */
    public function newNumber($store_id)
    {
        $count  = $this
            ->where('store_id',$store_id)
            ->whereDate('created_at',date('Y-m-d'))
            ->count();
        $newCount   = $count+1;
        $serial_number  = date('Ymd').sprintf('%05s',$store_id).sprintf('%05s',$newCount);
        return $serial_number;
    }

}