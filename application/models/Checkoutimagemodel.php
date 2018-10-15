<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/10/12 0012
 * Time:        16:58
 * Describe:
 */
class Checkoutimagemodel extends Basemodel
{
    protected $table    = 'boss_checkout_image';

    public static function store($checkout_id,$images)
    {
        $arr    = [];
        foreach ($images as $image) {
            $arr[]  = ['checkout_id'=>$checkout_id,'url'=>$image];
        }
        Checkoutimagemodel::insert($arr);
        return true;
    }

}
