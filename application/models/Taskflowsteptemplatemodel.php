<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/7/30 0030
 * Time:        10:59
 * Describe:    任务流步骤模板
 */
class Taskflowsteptemplatemodel extends Basemodel
{
    const TYPE_CHECKOUT = 'CHECKOUT';
    const TYPE_CHECKOUT_NO_LIABILITY = 'NO_LIABILITY';  //免责退房任务流
    const TYPE_CHECKOUT_UNDER_CONTRACT = 'UNDER_CONTRACT';  //违约退房任务流
    const TYPE_PRICE    = 'PRICE';
    const TYPE_RESERVE  = 'RESERVE';
    const TYPE_SERVICE  = 'SERVICE';
    const TYPE_WARNING  = 'WARNING';    //警告
    const GROUP_NOTICE  = 'NOTICE';     //通知类任务流
    const GROUP_AUDIT   = 'AUDIT';      //审核类任务流
    protected $table    = 'boss_taskflow_step_template';

    protected $fillable = [

    ];

}
