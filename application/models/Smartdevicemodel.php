
<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/20 0020
 * Time:        16:12
 * Describe:    BOSS
 *智能设备表
 */

class Smartdevicemodel extends Basemodel{

    protected $table    = 'boss_smart_device';
    protected $hidden   = ['created_at','updated_at','deleted_at'];

    public function room(){
        return $this->belongsTo(Roomdotmodel::class,'room_id')->select('id','number');
    }

    public function store(){
        return $this->belongsTo(Storemodel::class,'store_id')->select('id');
    }
}
