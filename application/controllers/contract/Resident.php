<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * User: wws
 * Date: 2018-05-24
 * Time: 09:23
 *   运营住户合同
 */

class Resident extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('contractmodel');
    }

    /**
     * 住户管理合同信息
     */
    public function resident()
    {
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $this->load->model('couponmodel');
        $this->load->model('activitymodel');
        $this->load->model('storemodel');
        $post   = $this->input->post(NULL,true);
        $serial = $post['id'];
        $filed  = ['id','contract_id','resident_id','store_id','room_id','status','created_at'];
        $resident = Contractmodel::where('resident_id',$serial)->with('store')->with('roomunion')->with('residents')->get($filed);
        $this->api_res(0,['resident'=>$resident]);
    }



}