<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/20 0020
 * Time:        16:03
 * Describe:    BOSS
 * 楼栋表
 */
class Buildingmodel extends Basemodel{

    protected $table    = 'buildings';

    protected $hidden   = [];

    //楼栋所属的门店信息
    public function store(){

        return $this->belongsTo(Storemodel::class,'store_id');
    }

    //楼栋的房间
    public function room(){

        return $this->hasMany(Roommodel::class,'building_id');
    }


}
