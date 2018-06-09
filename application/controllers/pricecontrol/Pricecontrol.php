<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/6/8
 * Time:        21:57
 * Describe:    调价
 */
class Pricecontrol extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('roomunionmodel');
    }

    /**
     * 调价列表
     */
    public function priceControl()
    {
        $this->load->model('storemodel');
        $this->load->model('buildingmodel');
        $this->load->model('roomtypemodel');

        $post  =$this->input->post(null,true);
        $page  = isset($post['page'])?intval($post['page']):1;
        $offset= PAGINATE * ($page - 1);
        $filed = ['id','store_id','building_id','number','room_type_id','rent_price','property_price','updated_at'];
        $where = [];
        if(!empty($post['store_id'])){$where['store_id'] = intval($post['store_id']);};
        if(!empty($post['building_id'])){$where['building_id'] = intval($post['building_id']);};
        if(!empty($post['number'])){$where['number'] = trim($post['number']);};

        $count = $count = ceil(Roomunionmodel::where($where)->count()/PAGINATE);
        if ($page>$count||$page<1){
            $this->api_res(0,['list'=>[]]);
            return;
        }else {
            $price = Roomunionmodel::with('store_s')->with('building_s')->with('room_type')
                ->where($where)->take(PAGINATE)
                ->skip($offset)->get($filed)->map(function ($s){
                    $s->updated = date('Y-m-d',strtotime($s->updated_at->toDateTimeString()));
                    return $s;
                })->toArray();

            $this->api_res(0, ['list' => $price, 'count' => $count]);
        }
    }

    /**
     *  调价(物业房租)
     */
    public function rentPrice()
    {
        $post = $this->input->post(null,true);
        if(!$this->validation())
        {
            $fieldarr   = ['rent_price','property_price'];
            $this->api_res(1002,['errmsg'=>$this->form_first_error($fieldarr)]);
            return false;
        }
        if ($post['id']){
            $id = intval($post['id']);
            $rent_price = $post['rent_price'];
            $property_price = $post['property_price'];
            $price = Roomunionmodel::findorFail($id);
            $price->rent_price = $rent_price;
            $price->property_price = $property_price;
            if ($price->save()){
                $this->api_res(0,[]);
            }else{
                $this->api_res(1009);
            }
        }else{
            $this->api_res(1002);
        }
    }

    /**
     * 水电价格
     */
    public function utilities()
    {
        $this->load->model('storemodel');
        $post = $this->input->post(null,true);
        if ($post['store_id']){
            $store_id = intval($post['store_id']);
            $price    = Storemodel::where('id',$store_id)->get(['water_price','hot_water_price','electricity_price'])->toArray();
            $this->api_res(0,$price);
        }else{
            $this->api_res(1002);
        }
    }

    /**
     * 调价（水电）
     */
    public function changeUtility()
    {
        $this->load->model('storemodel');
        $post = $this->input->post(null,true);
        if (isset($post['hot_water_price'])){$h_price = trim($post['hot_water_price']);}
        if (isset($post['water_price'])){$c_price = trim($post['water_price']);}
        if (isset($post['electricity_price'])){$e_price = trim($post['electricity_price']);}

        if ($post['store_id']){
            $store_id = intval($post['store_id']);
            $price    = Storemodel::where('id',$store_id)->first();
            $price->hot_water_price = $h_price;
            $price->water_price = $c_price;
            $price->electricity_price = $e_price;
            if ($price->save()){
                $this->api_res(0);
            }else{
                $this->api_res(1009);
            }
        }else{
            $this->api_res(1002);
        }
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
                'field' => 'rent_price',
                'label' => '住宿费',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'property_price',
                'label' => '物业费',
                'rules' => 'trim|required',
            ),
        );

        $this->form_validation->set_rules($config)->set_error_delimiters('','');
        return $this->form_validation->run();
    }

}