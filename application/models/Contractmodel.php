<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/20 0020
 * Time:        16:07
 * Describe:    BOSS
 * 合同表
 */
class Contractmodel extends Basemodel {

    protected $table    = 'contracts';

    protected $hidden   = [];

    //房间信息
    public function room(){

        return $this->belongsTo(Roommodel::class,'room_id');
    }

    //住户户信息
    public function resident(){

        return $this->belongsTo(Residentmodel::class,'resident_id');
    }


}