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
    const TYPE_PRICE    = 'PRICE';
    const TYPE_RESERVE  = 'RESERVE';
    const TYPE_SERVICE  = 'SERVICE';
    protected $table    = 'boss_taskflow_step_template';

    protected $fillable = [

    ];

}
