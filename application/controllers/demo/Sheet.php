<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/11 0011
 * Time:        14:23
 * Describe:
 */
class Sheet extends MY_Controller{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(){
//        $inputFileName = 'sheet.xlsx';
//        $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($inputFileName);
//        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
//        $reader->setReadDataOnly(true);
//        $spreadsheet = $reader->load($inputFileName);
//        //
//        $sheet  = $spreadsheet->getActiveSheet();
//        $row    = $sheet->getHighestRow();
//        $column = $sheet->getHighestColumn();
//        var_dump($row);die();
//        $array  =[];
//        $message        = [];
//        for ($i=2;$i<$row;$i++){
//            for ($j='A';$j!=$column;$j++){
//                $transfer   = ['A'=>'NA','B'=>'NB','C'=>'NC'];
//                if(array_key_exists($j,$transfer)){
//                    $value  = $sheet->getCell($j.$i)->getValue();
//                    if(empty($value)){
//                        array_push($message,$j.$i."为空");
//                        unset($array[$i]);
//                        break;
//                    }
//                    $array[$i][$transfer[$j]]=$value;
//                }
//            }
//            //....
//        }
       // var_dump($array);

//        var_dump($_SERVER);exit;

        $url    = $this->input->post('url');
        $f_open = fopen($url,'r');
        $file_name  = APPPATH.'cache/test.xlsx';
        file_put_contents(APPPATH.'cache/test.xlsx',$f_open);
        $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($file_name);
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
        $reader->setReadDataOnly(true);
        $excel = $reader->load($file_name);
        $sheet  = $excel->getActiveSheet();
        var_dump($sheet->toArray());


        exit;



        var_dump($_FILES);exit;

        $config = [
            'allowed_types' => 'xls|xlsx',
            'max_size'  => 40*1024,
        ];
        $this->load->library('excel',$config);
        if(!$this->excel->do_upload('file'))
        {

            $this->api_res(1004,array('error' => $this->excel->display_errors('','')));
        }else {
            //var_dump($this->excel->excel);
//            $sheet  = $this->excel->excel->getActiveSheet();
            $sheet  = $this->excel->excel->getSheetByName('Sheet2');
            $sheetArray = $sheet->toArray('A1','G1');
            var_dump($sheetArray);
//            $row    = $sheet->getHighestRow();
//            var_dump($row);

           // $oss_path   = $this->excel->data()['oss_path'];
           // $this->api_res(0,['file_url'=>config_item('cdn_path').$oss_path]);
        }



    }




    public function output(){
        $transfer   = [''];
        $data   = [[1,2,3,4,5],[2,3,4,5,6]];

        $this->out($transfer,$data);

    }

    /*
     * 载入
     */
    public function load(){

    }

    /**
     * 判断是否是excel文件
     */

    /**
     * 输出
     */
    public function out($transfer,$data){

        $spreadsheet    = new Spreadsheet();
        $sheet  = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1','列A1');
        $data   = [['s'=>1,2,3,4,5],[2,3,4,5,6]];
        $sheet->fromArray($data,null,'A2');
        $writer = new Xlsx($spreadsheet);

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-excel");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="report.xls"');
        header("Content-Transfer-Encoding:binary");
        $writer->save('php://output');
    }

}
