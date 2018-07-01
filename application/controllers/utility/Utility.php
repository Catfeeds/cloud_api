<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/6/28
 * Time:        14:21
 * Describe:    财务-水电费
 */

class Utility extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('meterreadingmodel');
    }

    /**
     * 水电列表
     */
    public function listUtility()
    {
        $this->load->model('meterreadingtransfermodel');
        $this->load->model('storemodel');
        $this->load->model('buildingmodel');
        $this->load->model('roomunionmodel');
        $post = $this->input->post(null,true);
        $page  = !empty($post['page'])?intval($post['page']):1;
        $offset= PAGINATE * ($page - 1);
        $where      = [];
        if(!empty($post['building_id'])){$where['building_id'] = intval($post['building_id']);};

        if(!empty($post['store_id'])){$where['store_id'] = intval($post['store_id']);}
        if(!empty($post['status'])){$where['confirmed'] = intval($post['status']);}
        if(!empty($post['type'])){$where['type'] = $post['type'];}
        $filed  = ['id','store_id','building_id','room_id','type','last_reading','last_time','this_reading','updated_at','confirmed'];
        $room_ids = [];
        if(!empty($post['number'])){
            $number = trim($post['number']);
            $room_id = Roomunionmodel::where('number',$number)->get(['id'])->toArray();
            if ($room_id){
                foreach ($room_id as $key=>$value){
                    array_push($room_ids,$room_id[$key]['id']);
                }
            }
            $count  = ceil(Meterreadingtransfermodel::where($where)->whereIn('room_id',$room_ids)->count()/PAGINATE);
            if ($page>$count||$page<1){
                $this->api_res(0,['list'=>[]]);
                return;
            }else{
                $utility = Meterreadingtransfermodel::where($where)->whereIn('room_id',$room_ids)->orderBy('updated_at', 'DESC')
                    ->with('store', 'building', 'roomunion')->take(PAGINATE)->skip($offset)
                    ->get($filed)->map(function($s){
                        switch ($s->type){
                            case 'ELECTRIC_METER':
                                $s->diff = number_format($s->this_reading-$s->last_reading,2);
                                $s->price= number_format($s->diff*$s->store->electricity_price,2);
                                break;
                            case 'COLD_WATER_METER':
                                $s->diff = number_format($s->this_reading-$s->last_reading,2);
                                $s->price= number_format($s->diff*$s->store->water_price,2);
                                break;
                            case 'HOT_WATER_METER':
                                $s->diff = number_format($s->this_reading-$s->last_reading,2);
                                $s->price= number_format($s->diff*$s->store->hot_water_price,2);
                                break;
                            default :
                                $s->diff = number_format($s->this_reading-$s->last_reading,2);
                                $s->price= 0;
                                break;
                        }
                        return $s;
                    })->toArray();
            }
        }else{
            $count  = ceil(Meterreadingtransfermodel::where($where)->count()/PAGINATE);
            if ($page>$count||$page<1){
                $this->api_res(0,['list'=>[]]);
                return;
            }else{
                $utility = Meterreadingtransfermodel::where($where)->orderBy('updated_at', 'DESC')
                    ->with('store', 'building', 'roomunion')->take(PAGINATE)->skip($offset)
                    ->get($filed)->map(function($s){
                        switch ($s->type){
                            case 'ELECTRIC_METER':
                                $s->diff = number_format($s->this_reading-$s->last_reading,2);
                                $s->price= number_format($s->diff*$s->store->electricity_price,2);
                                break;
                            case 'COLD_WATER_METER':
                                $s->diff = number_format($s->this_reading-$s->last_reading,2);
                                $s->price= number_format($s->diff*$s->store->water_price,2);
                                break;
                            case 'HOT_WATER_METER':
                                $s->diff = number_format($s->this_reading-$s->last_reading,2);
                                $s->price= number_format($s->diff*$s->store->hot_water_price,2);
                                break;
                            default :
                                $s->diff = number_format($s->this_reading-$s->last_reading,2);
                                $s->price= 0;
                                break;
                        }
                        return $s;
                    })->toArray();
            }
        }
        $this->api_res(0, ['list'=>$utility,'count'=>$count]);
    }

    public function listUtility1()
    {
        $this->load->model('meterreadingtransfermodel');
        $this->load->model('storemodel');
        $this->load->model('buildingmodel');
        $this->load->model('roomunionmodel');
        $filed  = ['id','store_id','building_id','room_id','type','last_reading','last_time','this_reading','updated_at'];
        $utility = Meterreadingtransfermodel::orderBy('store_id')->orderBy('number')
            ->with('store', 'building', 'roomunion')
            ->get($filed)->map(function($s){
                switch ($s->type){
                    case 'ELECTRIC_METER':
                        $s->diff = number_format($s->this_reading-$s->last_reading,2);
                        $s->price= number_format(floatval($s->diff)*$s->store->electricity_price,2);
                        break;
                    case 'COLD_WATER_METER':
                        $s->diff = number_format($s->this_reading-$s->last_reading,2);
                        $s->price= number_format($s->diff*$s->store->water_price,2);
                        break;
                    case 'HOT_WATER_METER':
                        $s->diff = number_format($s->this_reading-$s->last_reading,2);
                        $s->price= number_format($s->diff*$s->store->hot_water_price,2);
                        break;
                    default :
                        $s->diff = number_format($s->this_reading-$s->last_reading,2);
                        $s->price= 0;
                        break;
                }
                return $s;
            })->toArray();
        $newUtility = [];
        foreach ($utility as $key=>$value){
            $res = [];
            $res['store'] = $utility[$key]['store']['name'];
            $res['building'] = $utility[$key]['building']['name'];
            $res['number'] = $utility[$key]['roomunion']['number'];
            $res['type'] = $utility[$key]['type'];
            $res['last_reading'] = $utility[$key]['last_reading'];
            $res['this_reading'] = $utility[$key]['this_reading'];
            $res['diff'] = $utility[$key]['diff'];
            $res['price'] = $utility[$key]['price'];
            $newUtility[]   = $res;
        }
        $objPHPExcel    = new Spreadsheet();
        $sheet  = $objPHPExcel->getActiveSheet();
        $i = 1;
        $objPHPExcel->getActiveSheet()->setCellValue('A'.$i , '门店');
        $objPHPExcel->getActiveSheet()->setCellValue('B'.$i , '楼栋');
        $objPHPExcel->getActiveSheet()->setCellValue('C'.$i , '房间号');
        $objPHPExcel->getActiveSheet()->setCellValue('D'.$i , '设备类型');
        $objPHPExcel->getActiveSheet()->setCellValue('E'.$i , '上次读数');
        $objPHPExcel->getActiveSheet()->setCellValue('F'.$i , '本次读数');
        $objPHPExcel->getActiveSheet()->setCellValue('G'.$i , '差值');
        $objPHPExcel->getActiveSheet()->setCellValue('H'.$i , '价格');
        $sheet->fromArray($newUtility,null,'A2');
        $writer = new Xlsx($objPHPExcel);
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-excel");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="meterReadingTemplate.xlsx"');
        header("Content-Transfer-Encoding:binary");
        $writer->save('php://output');
    }
}