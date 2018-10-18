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

    protected $dates = ['time', 'created_at', 'updated_at','handle_time'];

    protected $casts = [
        'data' => 'array',
    ];

    const STATUS_APPLIED            = 'APPLIED';    //用户申请退房
    const STATUS_CONFIRM            = 'CONFIRM';    //员工已确认处理但未完成验房
    const STATUS_CHECKED            = 'CHECKED';    //生成退房记录，未提交审核（已验房）
    const STATUS_SIGNATURE          = 'SIGNATURE';  //已签字（用户签署）
    const STATUS_AUDIT              = 'AUDIT';      //待审核（审核中）
    const STATUS_UNPAID             = 'UNPAID';     //审核通过未付款
    const STATUS_CLOSED             = 'CLOSED';     //已关闭退房单
    const STATUS_COMPLETED          = 'COMPLETED';  //已完成

    const STATUS_PENDING            ='PENDING';                 //检查一下准备废弃
    const STATUS_BY_MANAGER         ='BY_MANAGER';              //检查一下准备废弃
    const STATUS_MANAGER_APPROVED           ='MANAGER_APPROVED';//检查一下准备废弃
    const STATUS_PRINCIPAL_APPROVED         ='PRINCIPAL_APPROVED';//检查一下准备废弃


    const TYPE_NORMAL               = 'NORMAL_REFUND';  //正常退房
    const TYPE_ABNORMAL             = 'UNDER_CONTRACT'; //违约退房
    const TYPE_NOLIABILITY          = 'NO_LIABILITY';   //免责退房

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
}
