<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      chenkk<cooook@163.com>
 * Date:        2018/5/28
 * Time:        16:25
 * Describe:    记录访问url
 */
class Operationrecordmodel extends Basemodel
{

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    protected $table    = 'operations';

    protected $hidden   = ['created_at', 'updated_at'];
}