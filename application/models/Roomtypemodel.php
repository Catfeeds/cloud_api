<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/20 0020
 * Time:        16:16
 * Describe:    BOSS
 * 房型表
 */
class Roomtypemodel extends Basemodel{

    protected $table    = 'room_types';

    protected $hidden   = '';

    //房型的合同模板
    public function contracttemplate(){
        //保留历史模板，只显示最后更新的模板
        return $this->hasMany(Contracttemplatemodel::class,'room_type_id');
    }

    //房型的房间
    public function room(){

        return $this->hasMany(Roommodel::class,'room_type_id');
    }


}
