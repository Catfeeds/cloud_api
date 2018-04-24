<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/4/23
 * Time:        9:59
 * Describe:    [boss端]服务类型model
 */
class Servicetypemodel extends Basemodel
{
    protected $table = 'service_type';
    protected $hidden   = ['created_at','updated_at','deleted_at'];

    public function services()
    {
        return $this->hasMany(Serviceordermodel::class, 'service_id');
    }
}