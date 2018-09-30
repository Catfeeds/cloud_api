<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/4/26
 * Time:        15:17
 * Describe:    服务管理-预约订单
 */
class Reserveorder extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('reserveordermodel');
    }

    /**
     * 返回预约订单列表
     */
    public function listReserveorder() {
        $this->load->model('employeemodel');
        $this->load->model('storemodel');
        $post = $this->input->post(NULL, true);
        $page = isset($post['page']) ? intval($post['page']) : 1;
        $offset = PAGINATE * ($page - 1);
        $where  = array();
        $filed  = ['id', 'time', 'name', 'phone', 'visit_by', 'work_address',
            'require', 'info_source', 'employee_id', 'status', 'remark','store_id'];

        if (!empty($post['store_id'])) {$where['store_id'] = intval($post['store_id']);}
        if (!empty($post['visit_type'])) {$where['visit_by'] = trim($post['visit_type']);}
        $store_ids = $this->employee_store->store_ids;
        if (empty($where)) {
            $count = ceil(Reserveordermodel::whereIn('store_id', $store_ids)->count() / PAGINATE);
            if ($page > $count || $page < 1) {
                $this->api_res(0, ['list' => []]);
                return;
            } else {
                $reserve = Reserveordermodel::whereIn('store_id', $store_ids)->with('employee')->with('store')
                    ->take(PAGINATE)->skip($offset)
                    ->orderBy('id', 'desc')->get($filed)->toArray();
            }
        } else {
            $count = ceil(Reserveordermodel::where($where)->whereIn('store_id', $store_ids)->count() / PAGINATE);
            if ($page > $count || $page < 1) {
                $this->api_res(0, ['list' => []]);
                return;
            } else {
                $reserve = Reserveordermodel::with('employee')->with('store')->whereIn('store_id', $store_ids)->where($where)
                    ->take(PAGINATE)->skip($offset)
                    ->orderBy('id', 'desc')->get($filed)->toArray();
            }
        }
        $this->api_res(0, ['list' => $reserve, 'count' => $count]);
    }

    public function ReserveorderToExcel(){
        $this->load->model('employeemodel');
        $this->load->model('storemodel');
        $store_id = $this->input->post('store_id');
        $data = $this->input->post('data');
        if(!$store_id || !$data){
            $this->api_res(1002);
            return false;
        }

        $filed  = ['id', 'time', 'name', 'phone', 'visit_by', 'work_address',
            'require', 'info_source', 'employee_id', 'status', 'remark','store_id'];
        $store_ids = $this->employee_store->store_ids;
        $reserve = Reserveordermodel::with('employee')->with('store')->where('store_id', $store_id)->where('store_id', $store_id)
            ->whereIn('store_id', $store_ids)->where('created_at', '>=', $data)->orderBy('id', 'desc')->get($filed);
        if(!$reserve){
            $this->api_res(1007);
            return false;
        }
        $status = new Reserveordermodel();
        foreach ($reserve as $order) {
            $res                 = [];
            $res['store_name']   = $order->store->name;
            $res['time']         = $order->time;
            $res['visit_by']     = $status->is_visit_by($order->visit_by);
            $res['name']         = $order->name;
            $res['phone']        = $order->phone;
            $res['work_address'] = empty($order->work_address) ? '' : $order->work_address;
            $res['require']      = empty($order->require) ? '' : $order->require;
            $res['info_source']  = empty($order->info_source) ? '' : $order->info_source;
            $res['employee']     = empty($order->employee->name) ? '' : $order->employee->name;
            $res['status']       = $status->is_reserve($order->status);
            $res['remark']       = empty($order->remark) ? '' : $order->remark;
            $reserve_excel[]     = $res;
            $store               =  $order->store->name;
        }

        $filename = date('Y-m-d-H:i:s') . '导出' . $data  . '_预约数据.Xlsx';
        $row      = count($reserve_excel) + 3;
        $phpexcel = new Spreadsheet();
        $sheet    = $phpexcel->getActiveSheet();
        $this->createPHPExcel($phpexcel, $filename); //创建excel
        $this->setExcelTitle($phpexcel, $store, $data); //设置表头
        $this->setExcelFirstRow($phpexcel); //设置各字段名称
        $sheet->fromArray($reserve_excel, null, 'A4'); //想excel中写入数据
        $this->setExcelColumnWidth($phpexcel); //设置Excel每列宽度
        $this->setAlignCenter($phpexcel, $row); //设置记录值居中
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($phpexcel, 'Xlsx');
        if(!headers_sent()){
            header("Pragma: public");
            header("Expires: 0");
            header("Content-Type:application/octet-stream");
            header("Content-Transfer-Encoding:binary");
            header('Cache-Control: max-age=0');
            header("Content-Disposition:attachment;filename=$filename");
        }
        $writer->save('php://output');
        exit;
    }

    private function createPHPExcel(Spreadsheet $phpexcel, $filename) {
        $phpexcel->getProperties()
            ->setCreator('梵响数据')
            ->setLastModifiedBy('梵响数据')
            ->setTitle($filename)
            ->setSubject($filename)
            ->setDescription($filename)
            ->setKeywords($filename)
            ->setCategory($filename);
        $phpexcel->setActiveSheetIndex(0);
        return $phpexcel;
    }
    private function setExcelTitle(Spreadsheet $phpexcel, $store, $data) {
        $phpexcel->getActiveSheet()
            ->mergeCells('A1:O2')
            ->setCellValue('A1', "$store" . "$data" . '预约统计')
            ->getStyle("A1:O2")
            ->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $phpexcel->getActiveSheet()->getCell('A1')->getStyle()->getFont()->setSize(16);
    }

    private function setExcelColumnWidth(Spreadsheet $phpexcel) {
        $phpexcel->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $phpexcel->getActiveSheet()->getColumnDimension('B')->setWidth(22);
        $phpexcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
        $phpexcel->getActiveSheet()->getColumnDimension('D')->setWidth(12);
        $phpexcel->getActiveSheet()->getColumnDimension('E')->setWidth(12);
        $phpexcel->getActiveSheet()->getColumnDimension('F')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('H')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('I')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('J')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('K')->setWidth(10);
    }
    private function setAlignCenter(Spreadsheet $phpexcel, $row) {
        $phpexcel->getActiveSheet()
            ->getStyle("A3:N{$row}")
            ->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    }

    private function setExcelFirstRow(Spreadsheet $phpexcel) {
        $phpexcel->getActiveSheet()->setCellValue('A3' , '门店名称')
        ->setCellValue('B3' , '预约日期')
        ->setCellValue('C3' , '来访类型')
        ->setCellValue('D3' , '姓名')
        ->setCellValue('E3' , '手机号码')
        ->setCellValue('F3' , '工作地点')
        ->setCellValue('G3' , '需求')
        ->setCellValue('H3' , '信息来源')
        ->setCellValue('I3' , '接待人')
        ->setCellValue('J3' , '状态')
        ->setCellValue('K3' , '备注');
    }
}
