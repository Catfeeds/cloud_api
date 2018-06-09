<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/6/9
 * Time:        15:06
 * Describe:    住户
 */
class Resident extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('residentmodel');
    }

    /**
     * 展示住户列表
     */
    public function showResident()
    {
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $this->load->model('customermodel');
        $post = $this->input->post(null,true);
        $page  = isset($post['page'])?intval($post['page']):1;
        $offset= PAGINATE * ($page - 1);
        $filed = ['id','name','customer_id','phone','room_id','card_number','created_at','status'];
        $where = [];
        if(!empty($post['store_id'])){$where['store_id'] = intval($post['store_id']);};
        if(!empty($post['name'])){$where['name'] = trim($post['name']);};

        $count = $count = ceil(Residentmodel::where($where)->count()/PAGINATE);
        if ($page>$count||$page<1){
            $this->api_res(0,['list'=>[]]);
            return;
        }else {
            $resident = Residentmodel::with('room')->with('customer_s')->where($where)->take(PAGINATE)
                    ->skip($offset)->get($filed)->map(function ($s){
                    $s->room->store_id = (Storemodel::where('id',$s->room->store_id)->get(['name']))[0]['name'];
                    $s->createdat = date('Y-m-d',strtotime($s->created_at->toDateTimeString()));
                    return $s;
                })->toArray();
            $this->api_res(0, ['list' => $resident, 'count' => $count]);
        }
    }

    /**
     * 住户基本信息
     */
    public function residentInfo()
    {
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $this->load->model('customermodel');
        $post = $this->input->post(null,true);
        if (isset($post['id'])){
            $resident_id = intval($post['id']);
            $filed = ['id','name','customer_id','phone','card_type','card_number','card_one','card_two','card_three','alternative','alter_phone'];
            $resident = Residentmodel::with('customer_s')
                ->where('id',$resident_id)->get($filed)
                ->map(function ($s){
                    $s->card_one = $this->fullAliossUrl($s->card_one);
                    $s->card_two = $this->fullAliossUrl($s->card_two);
                    $s->card_three = $this->fullAliossUrl($s->card_three);
                    return $s;
                })
                ->toArray();
            $this->api_res(0, $resident);
        }else{
            $this->api_res(1002);
        }
    }

    /**
     * 修改住户信息
     */
    public function updateResident()
    {
        $this->load->model('customermodel');
        $post = $this->input->post(null,true);
        $id = intval($post['id']);
        $customer_id = intval($post['customer_id']);
        if(!$this->validation())
        {
            $fieldarr   = ['name','gender','phone','card_type','card_number','card_one','card_two','card_three','alternative','alter_phone'];
            $this->api_res(1002,['errmsg'=>$this->form_first_error($fieldarr)]);
            return false;
        }
        $resident   = Residentmodel::findOrFail($id);
        $customer   = Customermodel::findOrFail($customer_id);
        $resident->fill($post);

        $card_one  = $this->splitAliossUrl($post['card_one']);
        $card_one = json_encode($card_one);
        $resident->card_one=$card_one;

        $card_two  = $this->splitAliossUrl($post['card_two']);
        $card_two = json_encode($card_two);
        $resident->card_two=$card_two;

        $card_three  = $this->splitAliossUrl($post['card_three']);
        $card_three = json_encode($card_three);
        $resident->card_three=$card_three;

        $customer->gender = $post['gender'];
        if($resident->save())
        {
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }
    }

    /**
     * 住户合同信息
     */
    public function contract()
    {
        $this->load->model('roomunionmodel');
        $this->load->model('contractmodel');
        $this->load->model('couponmodel');
        $this->load->model('activitymodel');
        $this->load->model('storemodel');
        $post   = $this->input->post(NULL,true);
        $serial = intval($post['id']);
        $filed  = ['id','contract_id','resident_id','store_id','room_id','status','created_at'];
        $resident = Contractmodel::where('id',$serial)->with('store')->with('roomunion')->with('residents')->get($filed);
        $this->api_res(0,['resident'=>$resident]);
    }


    /**
     * @return mixed
     * 表单验证
     */
    private function validation()
    {
        $this->load->library('form_validation');
        $config = array(
            array(
                'field' => 'name',
                'label' => '姓名',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'gender',
                'label' => '性别',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'phone',
                'label' => '联系电话',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'card_type',
                'label' => '证件类型',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'card_number',
                'label' => '证件号',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'card_one',
                'label' => '证件正面',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'card_two',
                'label' => '证件反面',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'card_three',
                'label' => '手持证件',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'alternative',
                'label' => '紧急联系人',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'alter_phone',
                'label' => '紧急联系人电话',
                'rules' => 'trim|required',
            ),
        );
        $this->form_validation->set_rules($config)->set_error_delimiters('','');
        return $this->form_validation->run();
    }
}