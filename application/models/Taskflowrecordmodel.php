<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/8/10 0010
 * Time:        10:29
 * Describe:    任务流审核记录
 */
class Taskflowrecordmodel extends Basemodel
{
    const STATE_AUDIT   = 'AUDIT';//未审核
    const STATE_APPROVED    = 'APPROVED';//已审核
    const STATE_UNAPPROVED    = 'UNAPPROVED';//审核未通过
    const STATE_CLOSED    = 'CLOSED';//关闭
    const TYPE_CHECKOUT = 'CHECKOUT';
    const TYPE_PRICE    = 'PRICE';
    protected $table    = 'boss_taskflow_record';

    protected $fillable = [
        'taskflow_id',
        'company_id',
        'store_id',
        'name',
        'seq',
        'type',
        'employee_id',
        'status',
        'remark'
    ];

    public function taskflow()
    {
        return $this->belongsTo(Taskflowmodel::class,'taskflow_id');
    }

    /**
     * 审核记录的员工
     */
    public function employee()
    {
       return $this->belongsTo(Employeemodel::class,'employee_id');
    }

}
