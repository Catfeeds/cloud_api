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

        $post   = $this->input->post(null,true);
        $page   = intval(isset($post['page']) ? $post['page'] : 1);
        $offset = PAGINATE * ($page-1);
        $filed = ['name', 'phone', 'position_id', 'store_names', 'hiredate', 'status'];
        $where  = isset($post['store_id']) ? ['store_id'=>$post['store_id']] : [];
        if(isset($post['city']) &&! empty($post['city'])){
            $store_ids  = Storemodel::where('city',$post['city'])->get(['id'])->map(function($s){
                return $s['id'];
            });
            $count  = ceil((Employeemodel::whereIn('store_id',$store_ids)->where($where)->count())/PAGINATE);
            if($page>$count){
                $this->api_res(0,['count'=>$count,'list'=>[]]);
                return;
            }
            $category = Employeemodel::with(['position' => function ($query) {
                        $query->select('id', 'name');
            }])->whereIn('store_id',$store_ids)->where($where)
                ->offset($offset)->limit(PAGINATE)->orderBy('id','desc')->get($filed);
            $this->api_res(0,['count'=>$count,'list'=>$category]);
            return;
        }
        $count  = ceil((Employeemodel::all()->count())/PAGINATE);
        if($page>$count){
            $this->api_res(0,['count'=>$count,'list'=>[]]);
            return;
        }
        $category = Employeemodel::with(['position' => function ($query) {
            $query->select('id', 'name');
        }])->offset($offset)->limit(PAGINATE)->orderBy('id','desc')->get($filed);

        $this->api_res(0,['count'=>$count,'list'=>$category]);
    }
}