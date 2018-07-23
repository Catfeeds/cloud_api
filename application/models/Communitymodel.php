<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/4 0004
 * Time:        10:36
 * Describe:    分布式 小区
 */
class Communitymodel extends Basemodel {
    public function __construct() {
        parent::__construct();
    }

    protected $table = "boss_community";

    protected $fillable = [
        'store_id', 'status', 'name', 'province', 'city', 'district', 'address', 'describe', 'history', 'shop', 'relax', 'bus',
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    //小区的房间
    public function room() {
        return $this->hasMany(Roomdotmodel::class, 'community_id')->select(['community_id']);
    }

    //小区所属门店
    public function store() {
        return $this->belongsTo(Storemodel::class, 'store_id')->select('id', 'name', 'city');
    }

}
