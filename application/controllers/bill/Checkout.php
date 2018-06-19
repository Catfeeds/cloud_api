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
        $where  = [];
        empty($input['store_id'])?:$where['store_id']=$input['store_id'];
        $search = empty($input['search'])?'':$input['search'];
        $page   = isset($input['page'])?$input['page']:1;
        $offset = ($page-1)*PAGINATE;

        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $this->load->model('employeemodel');

        $count  = ceil((Checkoutmodel::with('store','roomunion','resident','employee')
                ->where($where)->count())/PAGINATE);

        if($count<$page){
            $this->api_res(0,[]);
            return;
        }

        $orders = Checkoutmodel::with('store','roomunion','resident','employee')
            ->where($where)->get()->toArray();

        $this->api_res(0,['orders'=>$orders,'total_page'=>$count]);






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

