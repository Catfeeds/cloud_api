<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/21 0021
 * Time:        11:42
 * Describe:    BOSS
 * 房屋信息
 */

class Housemodel extends Basemodel{

    protected $table    = 'houses';

    //房屋的房间信息
    public function room(){

        return $this->hasMany(Roommodel::class,'house_id');
    }
}
