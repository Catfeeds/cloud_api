<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * User: wws
 * Date: 2018-05-24
 * Time: 09:23
 *   运营住户合同
 */

class Resident extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('contractmodel');
    }

    /**
     * 住户管理合同信息
     */
    public function resident() {
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $this->load->model('couponmodel');
        $this->load->model('activitymodel');
        $this->load->model('storemodel');
        $post     = $this->input->post(NULL, true);
        $serial   = $post['id'];
        $resident = Contractmodel::where('resident_id', $serial)->with('store')->with('roomunion')->with('residents')
            ->with('employee')
            ->orderBy('created_at','DESC')
            ->get()
            ->map(function($s){
                if($s->rent_type=='RESERVE'){
                    $s->begin_time    = $s->resident->reserve_begin_time->format('Y-m-d');
                    $s->end_time    = $s->resident->reserve_end_time->format('Y-m-d');
                }else{
                    $s->begin_time    = $s->resident->begin_time->format('Y-m-d');
                    $s->end_time    = $s->resident->end_time->format('Y-m-d');
                }
                return $s;
            });
        $this->api_res(0, ['resident' => $resident]);
    }
}
