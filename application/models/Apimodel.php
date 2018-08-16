<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/21 0021
 * Time:        15:59
 * Describe:
 */
class Apimodel extends Basemodel
{

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    protected $table    = 'boss_api';

    protected $fillable = [];

    protected $hidden   = ['created_at','updated_at','deleted_at'];

}