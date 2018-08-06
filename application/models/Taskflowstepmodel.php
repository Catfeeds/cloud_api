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

    protected $table    = 'boss_taskflow_step';

    protected $fillable = [

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