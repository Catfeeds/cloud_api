<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      chenkk<cooook@163.com>
 * Date:        2018/6/2
 * Time:        10:17
 * Describe:
 */

class Ownermodel extends Basemodel {

    public function __construct() {
        parent::__construct();
    }

    protected $table = 'boss_owner';

    protected $hidden = ['created_at', 'updated_at'];

    protected $fillable = [
        'house_id',
        'name',                     //姓名
        'phone',                    //电话
        'card_number',              //身份证
        'account',                  //持卡人
        'bank_card_number',         //卡号
        'own_account',              //是否自持（0，1）
        'bank_name',                //开户行
        'minimum_rent',             //保底租金
        'start_date',               //交付日期
        'end_date',                 //托管日期
        'contract_years',           //合同时长
        'rent_increase_rate',       //递增比例 [1,5,5,5,5]
        'address',
        'no_rent_days',             //免租期限(日)
    ];
    /**
     * 业主的房间
     */
    public function house() {
        return $this->belongsTo(Ownerhousemodel::class, 'house_id')->select(['id',
            'store_id',
            'layer',                //当前层数
            'area',                 //面积
            'room_count',           //几室
            'hall_count',           //几厅
            'kitchen_count',        //几厨
            'bathroom_count',       //几卫
            'number',               //房间号
            'unit',                 //单元
            'layer_total']);           //总楼层]);
    }

    /**
     * 业主关联的微信
     */
    public function customer() {
        return $this->belongsTo(Customermodel::class, 'customer_id');
    }

}
