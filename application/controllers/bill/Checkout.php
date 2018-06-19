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
    }

    //退房账单列表
    public function list(){
        $post   = $this->input->post(null,true);
        $page   = intval(isset($post['page'])?$post['page']:1);
        $offset = PAGINATE*($page-1);
        $field  = ['id','store_id','name','feature'];
        $this->load->model('storemodel');
        $where  = isset($post['store_id'])?['store_id'=>$post['store_id']]:[];
        if(isset($post['city'])&&!empty($post['city'])){
            $store_ids  = Storemodel::where('city',$post['city'])->get(['id'])->map(function($s){
                return $s['id'];
            });
            $count  = ceil((Roomtypemodel::with('store')->whereIn('store_id',$store_ids)->where($where)->count())/PAGINATE);
            if($page>$count){
                $this->api_res(0,['count'=>$count,'list'=>[]]);
                return;
            }
            $roomtypes = Roomtypemodel::with('store')->whereIn('store_id',$store_ids)->where($where)->offset($offset)->limit(PAGINATE)->orderBy('id','desc')->get($field);
            $this->api_res(0,['count'=>$count,'list'=>$roomtypes]);
            return;
        }
        $count  = ceil((Roomtypemodel::with('store')->where($where)->count())/PAGINATE);
        if($page>$count){
            $this->api_res(0,['count'=>$count,'list'=>[]]);
            return;
        }
        $roomtypes = Roomtypemodel::with('store')->where($where)->offset($offset)->limit(PAGINATE)->orderBy('id','desc')->get($field);
        $this->api_res(0,['count'=>$count,'list'=>$roomtypes]);



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

