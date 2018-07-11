<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/3
 * Time:        10:50
 * Describe:    智能设备-水表电表记录
 */
class Smartdevicerecordmodel extends Basemodel
{
    protected $table = 'boss_smart_device_record';
    protected $hidden = ['created_at','deleted_at'];
}