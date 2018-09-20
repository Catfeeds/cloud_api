<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
        $store_ids = explode(',', $this->employee->store_ids);
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
        $store_ids = explode(',', $this->employee->store_ids);
        $reserve = Reserveordermodel::with('employee')->with('store')->where('store_id', $store_id)->where('store_id', $store_id)
            ->whereIn('store_id', $store_ids)->where('created_at', '>', $data)->orderBy('id', 'desc')->get($filed);
        if(!$reserve){
            $this->api_res(1007);
            return false;
        }
        foreach ($reserve as $order) {
            $res                 = [];
            $res['store_name']   = $order->store->name;
            $res['time']         = $order->time;
            $res['visit_by']     = $order->visis_by;
            $res['name']         = $order->name;
            $res['phone']        = $order->phone;
            $res['work_address'] = empty($order->work_address) ? '' : $order->work_address;
            $res['require']      = empty($order->require) ? '' : $order->require;
            $res['info_source']  = empty($order->info_source) ? '' : $order->info_source;
            $res['employee']     = empty($order->employee->name) ? '' : $order->employee->name;
            $res['status']       = empty($order->status) ? '' : $order->status;
            $res['remark']       = empty($order->remark) ? '' : $order->remark;
            $reserve_excel[]     = $res;
            $store               =  $order->store->name;
        }
        print_r($res);die();
        $objPHPExcel = new Spreadsheet();
        $sheet       = $objPHPExcel->getActiveSheet();
        $i           = 1;
        $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, '门店名称');
        $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, '预约日期');
        $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, '来访类型');
        $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, '姓名');
        $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, '手机号码');
        $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, '工作地点');
        $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, '需求');
        $objPHPExcel->getActiveSheet()->setCellValue('H' . $i, '信息来源');
        $objPHPExcel->getActiveSheet()->setCellValue('I' . $i, '接待人');
        $objPHPExcel->getActiveSheet()->setCellValue('J' . $i, '状态');
        $objPHPExcel->getActiveSheet()->setCellValue('K' . $i, '备注');
        $sheet->fromArray($reserve_excel, null, 'A2');
        $writer = new Xlsx($objPHPExcel);
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-excel");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header("Content-Disposition:attachment;filename=$store.$data.'.xlsx'");
        header("Content-Transfer-Encoding:binary");
        $writer->save('php://output');

    }
}
