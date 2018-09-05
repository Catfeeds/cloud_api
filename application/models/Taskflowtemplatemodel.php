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
    const TYPE_CHECKOUT = 'CHECKOUT'; //退房
    const TYPE_CHECKOUT_NO_LIABILITY = 'NO_LIABILITY';  //免责退房任务流
    const TYPE_CHECKOUT_UNDER_CONTRACT = 'UNDER_CONTRACT';  //违约退房任务流
    const TYPE_PRICE    = 'PRICE';      //调价
    const TYPE_RESERVE  = 'RESERVE';    //预约看房
    const TYPE_SERVICE  = 'SERVICE';    //服务订单
    const TYPE_WARNING  = 'WARNING';    //警告
    const GROUP_NOTICE  = 'NOTICE';     //通知类任务流
    const GROUP_AUDIT   = 'AUDIT';      //审核类任务流
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

    /**
     * 通知类任务流类型
     */
    public function getNoticeTypes()
    {
        return [
            self::TYPE_SERVICE,
            self::TYPE_RESERVE,
            self::TYPE_WARNING
        ];
    }

    /**
     * 审核类任务流类型
     */
    public function getAuditTypes()
    {
        return [
            self::TYPE_PRICE,
            self::TYPE_CHECKOUT,
            self::TYPE_CHECKOUT_NO_LIABILITY,
            self::TYPE_CHECKOUT_UNDER_CONTRACT
        ];
    }
}
