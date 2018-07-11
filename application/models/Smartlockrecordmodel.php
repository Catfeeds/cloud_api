<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/3
 * Time:        10:54
 * Describe:    智能设备-门锁开门记录
 */

class Smartlockrecordmodel extends Basemodel
{
    protected $table = 'boss_smart_lock_record';
    protected $hidden = ['created_at','deleted_at'];
}