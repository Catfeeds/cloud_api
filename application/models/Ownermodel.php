<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      chenkk<cooook@163.com>
 * Date:        2018/6/2
 * Time:        10:17
 * Describe:
 */

class Ownermodel extends Basemodel
{

    public function __construct()
    {
        parent::__construct();
    }

    protected $table    = 'boss_owner';

    protected $hidden   = ['created_at', 'updated_at'];

    /**
     * 业主的房间
     */
    public function house()
    {
        return $this->belongsTo(Ownerhousemodel::class, 'house_id');
    }

    /**
     * 业主关联的微信
     */
    public function customer()
    {
        return $this->belongsTo(Customermodel::class, 'customer_id');
    }

}