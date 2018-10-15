<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/30 0030
 * Time:        19:32
 * Describe:
 */
class Checkoutmodel extends Basemodel {
    protected $table = 'boss_checkout_record';

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

    protected $dates = ['time', 'created_at', 'updated_at'];

    protected $casts = [
        'data' => 'array',
    ];

    const STATUS_APPLIED            = 'APPLIED'; //申请退房
    const STATUS_UNPAID             = 'UNPAID'; //填完一些基本信息, 待支付
    const STATUS_PENDING            = 'PENDING'; //支付完成, 等待提交审核
    const STATUS_BY_MANAGER         = 'BY_MANAGER'; //等待店长审核
    const STATUS_MANAGER_APPROVED   = 'MANAGER_APPROVED'; //店长审核完毕, 等待运营经理审核
    const STATUS_PRINCIPAL_APPROVED = 'PRINCIPAL_APPROVED'; //运营经理审核, 交由财务处理
    const STATUS_COMPLETED          = 'COMPLETED'; //财务处理完成, 完成退房流程
    const STATUS_AUDIT              = 'AUDIT';//待审核

    const TYPE_NORMAL   = 'NORMAL_REFUND';
    const TYPE_ABNORMAL = 'UNDER_CONTRACT';
    const TYPE_NOLIABILITY = 'NO_LIABILITY';

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
}
