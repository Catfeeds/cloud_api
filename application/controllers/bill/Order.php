<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use \PhpOffice\PhpSpreadsheet\Style\Alignment;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/4 0004
 * Time:        17:17
 * Describe:    流水 一个订单分为多个流水
 */
class Order extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('ordermodel');
    }

    public function download()
    {
        $input  = $this->input->post(null,true);

        $orders = Ordermodel::get()->toArray();

        var_dump($orders);




    }

    private function createPHPExcel($filename)
    {
        $phpexcel = new Spreadsheet();
        $phpexcel->getProperties()
            ->setCreator('梵响互动')
            ->setLastModifiedBy('梵响互动')
            ->setTitle($filename)
            ->setSubject($filename)
            ->setDescription($filename)
            ->setKeywords($filename)
            ->setCategory($filename);
        $phpexcel->setActiveSheetIndex(0);

        return $phpexcel;
    }

    private function setExcelTitle(Spreadsheet $phpexcel, $apartment, $start, $end)
    {
        $phpexcel->getActiveSheet()
            ->mergeCells('A1:N2')
            ->setCellValue('A1', $apartment->name . ' 订单流水统计' . $start .' - ' . $end)
            ->getStyle('A1')
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $phpexcel->getActiveSheet()->getCell('A1')->getStyle()->getFont()->setSize(16);
    }

    private function setExcelFirstRow(Spreadsheet $phpexcel)
    {
        $phpexcel->getActiveSheet()
            ->setCellValue('A3', '订单月份')
            ->setCellValue('B3', '确认时间')
            ->setCellValue('C3', '房号')
            ->setCellValue('D3', '住户')
            ->setCellValue('E3', '缴费金额（元）')
            ->setCellValue('F3', '住宿服务费（元）')
            ->setCellValue('G3', '物业服务费（元）')
            ->setCellValue('H3', '水电费（元）')
            ->setCellValue('I3', '物品租赁（元）')
            ->setCellValue('J3', '其他收费（元）')
            ->setCellValue('K3', '住宿押金（元）')
            ->setCellValue('L3', '其他押金（元）')
            ->setCellValue('M3', '缴费方式')
            ->setCellValue('N3', '备注');
    }


}