<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/9 0009
 * Time:        20:42
 * Describe:
 */

class Checkout extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('checkoutmodel');
        $this->load->model('storemodel');
        $this->load->model('residentmodel');
        $this->load->model('employeemodel');
    }

    //退房账单列表
    public function list(){
        $input  = $this->input->post(null,true);
        $page   = isset($input['page'])?$input['page']:1;
        $where  = [];
        empty($input['store_id'])?$where['store_id']=1:$where['store_id']=$input['store_id'];

        $offset = ($page-1)*PAGINATE;

        $checkout  = Checkoutmodel::with(['roomunion','store','resident','employee'])
            ->offset($offset)->limit(PAGINATE)
            ->where($where)
            ->orderBy('updated_at','desc')
            ->offset($offset)->limit(PAGINATE)->get();
        var_dump($checkout);




    }

    //退房账单更新
    public function update(){


    }

    //确定正常退房
    public function sure(){


    }

    //押金转收入退房

    public function Breach(){


    }








}

