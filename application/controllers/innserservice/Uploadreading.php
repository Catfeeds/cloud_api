<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/8/24
 * Time:        15:18
 * Describe:    上传水电读数-外部接口
 */
class Uploadreading extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('meterreadingtransfermodel');
    }

    /**
     * 上传冷水读数
     */
    public function coldWaterReading()
    {
        $this->load->model('meterreadingmodel');
        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');
        $this->load->model('smartdevicemodel');
        $store_id   = $this->input->post('store_id');
        $month      = $this->input->post('month');
        $year       = $this->input->post('year');
        $sheetArray = $this->input->post('data');
        $type       = Meterreadingtransfermodel::TYPE_WATER_C;
        $transfer   = new Meterreadingtransfermodel();
        $data       = $transfer->checkAndGetInputData($sheetArray);
        if(!empty($data['error'])){
            $this->api_res(10052,['error'=>$data['error']]);
            return;
        }
        //存储导入数据
        $res        = $transfer->writeReading($data,$store_id,$type,$year,$month);
        if (!empty($res)){
            $this->api_res(10051,['error'=>$res]);
        }else{
            $this->api_res(0);
        }
    }

    /**
     * 上传热水读数
     */
    public function hotWaterReading()
    {
        $this->load->model('meterreadingmodel');
        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');
        $this->load->model('smartdevicemodel');
        $store_id   = $this->input->post('store_id');
        $month      = $this->input->post('month');
        $year       = $this->input->post('year');
        $sheetArray = $this->input->post('data');
        $type       = Meterreadingtransfermodel::TYPE_WATER_H;
        $transfer   = new Meterreadingtransfermodel();
        $data       = $transfer->checkAndGetInputData($sheetArray);
        if(!empty($data['error'])){
            $this->api_res(10052,['error'=>$data['error']]);
            return;
        }
        //存储导入数据
        $res        = $transfer->writeReading($data,$store_id,$type,$year,$month);
        if (!empty($res)){
            $this->api_res(10051,['error'=>$res]);
        }else{
            $this->api_res(0);
        }
    }

    /**
     * 上传电表读数
     */
    public function electricityReading()
    {
        $this->load->model('meterreadingmodel');
        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');
        $this->load->model('smartdevicemodel');
        $store_id   = $this->input->post('store_id');
        $month      = $this->input->post('month');
        $year       = $this->input->post('year');
        $sheetArray = $this->input->post('data');
        $type       = Meterreadingtransfermodel::TYPE_ELECTRIC;
        $transfer   = new Meterreadingtransfermodel();
        $data       = $transfer->checkAndGetInputData($sheetArray);
        if(!empty($data['error'])){
            $this->api_res(10052,['error'=>$data['error']]);
            return;
        }
        //存储导入数据
        $res        = $transfer->writeReading($data,$store_id,$type,$year,$month);
        if (!empty($res)){
            $this->api_res(10051,['error'=>$res]);
        }else{
            $this->api_res(0);
        }
    }
}