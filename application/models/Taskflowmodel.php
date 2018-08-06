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
    const STATE_CLOSED  = 'CLOSED';
    protected $table    = 'boss_taskflow';

    protected $fillable = [

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
     * 所有的操作步骤记录
     */
    public function steps()
    {
        return $this->hasMany(Taskflowstepmodel::class,'taskflow_id');
    }

}