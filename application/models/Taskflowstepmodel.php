<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/7/30 0030
 * Time:        11:02
 * Describe:
 */
class Taskflowstepmodel extends Basemodel
{
    const STATE_AUDIT   = 'AUDIT';//未审核
    const STATE_APPROVED    = 'APPROVED';//已审核
    const STATE_UNAPPROVED    = 'UNAPPROVED';//审核未通过
    const STATE_CLOSED    = 'CLOSED';//关闭
    const TYPE_CHECKOUT = 'CHECKOUT';
    const TYPE_PRICE    = 'PRICE';
    const TYPE_RESERVE  = 'RESERVE';
    const TYPE_SERVICE  = 'SERVICE';

    protected $table    = 'boss_taskflow_step';

    protected $fillable = [
        'status',
    ];

    public function taskflow()
    {
        return $this->belongsTo(Taskflowmodel::class,'taskflow_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employeemodel::class,'employee_id');
    }
}