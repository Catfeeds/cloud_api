<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/7/30 0030
 * Time:        10:54
 * Describe:    任务流模板
 */
class Taskflowtemplatemodel extends Basemodel
{
    const TYPE_CHECKOUT = 'CHECKOUT';
    const TYPE_PRICE    = 'PRICE';
    const TYPE_RESERVE  = 'RESERVE';
    const TYPE_SERVICE  = 'SERVICE';
    protected $table    = 'boss_taskflow_template';

    protected $fillable = [

    ];

    protected $casts    = ['data'=>'array'];

    public function employee()
    {
        return $this->belongsTo(Employeemodel::class,'employee_id');
    }

    public function step_template()
    {
        return $this->hasMany(Taskflowsteptemplatemodel::class,'template_id')->orderBy('seq','ASC');
    }
}
