<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/10 0010
 * Time:        19:48
 * Describe:
 */
class test extends MY_Controller
{

    public function test1(){

        $this->load->model('activitymodel');
//        $acs=Activitymodel::get();
//        $acs->each(function($q){
//            $q->update(['description'=>555]);
//            //$q->save();
//        });
//        var_dump($acs->toArray());
        $activity   = New Activitymodel();
        //$activity->save();
        $id=$activity->id;
        echo $id;

    }
}
