<?php
/**
 * Author:      chenkk<cooook@163.com>
 * Date:        2018/6/2
 * Time:        10:17
 * Describe:
 */

class Ownerhousemodel extends Basemodel
{

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    protected $table    = 'boss_owner_house';

    protected $hidden   = ['created_at', 'updated_at'];

}