<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/14 0014
 * Time:        14:46
 * Describe:    员工管理
 */
class Employee extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('employeemodel');
    }

    public function showEmployee()
    {
        $this->load->model('positionmodel');
        $this->load->model('storemodel');

        $filed = ['name', 'phone', 'position_id', 'store_ids', 'hiredate', 'status'];
        $category = $this->employeemodel->with(['position' => function ($query) {
            $query->select('id','name');
        }])->get($filed)->map(function($a){
            $ids = json_decode($a->store_ids,true);
            if(!$ids){
                $store_names=[];
            }else{
                $store_names = $this->storemodel->find($ids)->map(function($b){
                    return $b->name;
                });
            }
            $a->store_names = $store_names;
            return $a;
        });

        $this->api_res(0, $category);
    }
}