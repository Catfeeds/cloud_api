<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/6/8
 * Time:        20:08
 * Describe:    合同后台操作
 */
class Contract extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('contractmodel');
    }

    /**
     * 合同列表
     */
    public function showContract() {
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $this->load->model('storemodel');
        $this->load->model('employeemodel');
        $post      = $this->input->post(null, true);
        $page      = isset($post['page']) ? intval($post['page']) : 1;
        $offset    = PAGINATE * ($page - 1);
        $where     = [];
        $store_ids = explode(',', $this->employee->store_ids);
        $filed     = ['id', 'contract_id', 'resident_id', 'room_id', 'type', 'created_at',
            'status', 'employee_id', 'store_id'];

        if (!empty($post['store_id'])) {
            $where['store_id'] = intval($post['store_id']);
        };
        if (!empty($post['status'])) {
            $where['status'] = trim($post['status']);
        };
        if (!empty($post['contract_id'])) {
            $search = trim($post['contract_id']);
        } else {
            $search = '';
        };
        //合同开始结束时间的起止日期
        if (!empty($post['begin_time_start'])) {
            $bt_start = trim($post['begin_time_start']);
        } else {
            $bt_start = date('Y-m-d H:i:s', 0);
        };

        if (!empty($post['begin_time_stop'])) {
            $bt_stop = trim($post['begin_time_stop']);
        } else {
            $bt_stop =  Residentmodel::max('begin_time');
        };

        if (!empty($post['end_time_start'])) {
            $et_start = trim($post['end_time_start']);
        } else {
            $et_start = date('Y-m-d H:i:s', 0);
        };

        if (!empty($post['end_time_stop'])) {
            $et_stop = trim($post['end_time_stop']);
        } else {
            $et_stop = Residentmodel::max('end_time');
        };

        $resident = Residentmodel::whereBetween('begin_time', [$bt_start, $bt_stop])
            ->whereBetween('end_time', [$et_start, $et_stop])
            ->get(['id'])
            ->toArray();
        $residents = [];
        if (!empty($resident)) {
            foreach ($resident as $key => $value) {
                $residents[] = $resident[$key]['id'];
            }
        }
        if(!empty($post['type'])){
            if($post['type']=='RESERVE'){
                $where['rent_type'] = 'RESERVE';
            }else{
                $where['rent_type'] = null;
            }
        }

        $contract = new Contractmodel();
        $count = ceil($contract->count( $store_ids, $residents, $where, $search)/PAGINATE);

        if ($page > $count || $page < 1) {
            $this->api_res(0, ['list' => []]);
            return;
        } else {
            $order = Contractmodel::with('employee')
                ->with('resident')
                ->with('store')
                ->with('roomunion')
                ->where($where)
                ->where(function ($query) use ($search) {
                    $query->orWhereHas('resident', function ($query) use ($search) {
                        $query->where('name', 'like', "%$search%");
                    })->orWhereHas('roomunion', function ($query) use ($search) {
                        $query->where('number', 'like', "%$search%");
                    });
                })
                ->whereIn('store_id', $store_ids)
                ->whereIn('resident_id', $residents)
                ->take(PAGINATE)
                ->skip($offset)
                ->get($filed)
                ->map(function ($s) {
                    $s['begin_time'] = date('Y-m-d', strtotime($s['resident']['begin_time']));
                    $s['end_time']   = date('Y-m-d', strtotime($s['resident']['end_time']));
                    $s['reserve_begin_time']    = date('Y-m-d', strtotime($s['resident']['reserve_begin_time']));
                    $s['reserve_end_time']    = date('Y-m-d', strtotime($s['resident']['reserve_end_time']));
                    return $s;
                })->toArray();
        }
        $this->api_res(0, ['list' => $order, 'count' => $count]);
    }

    /**
     * 合同导出
     */
    public function export()
    {
        $this->load->model('contractmodel');
        $this->load->model('residentmodel');
        $this->load->model('roomunionmodel');
        $this->load->model('coupontypemodel');
        $this->load->model('coupontypemodel');
        $this->load->model('activitymodel');
        $this->load->model('storemodel');
        $this->load->model('employeemodel');
        $input  = $this->input->post(null,true);
        $where  = [
            'store_id'    => $input['store_id'],
            'time_type'   => $input['time_type'], //'START','END'
            'type'        => $input['type'],        //'CHECKIN','RESERVE'
            'time_start'  => $input['time_start'],
            'time_end'    => $input['time_end'],
        ];
        if ($input['type']=='CHECKIN') {
            $type_name  = '入住';
        }else{
            $type_name  = '预定';
        }
        $store  = Storemodel::findOrFail($input['store_id']);
        $data   = $this->exportData($where);
        $filename   = date('Y-m-d',time()).$store->name.$type_name.'合同.Xlsx';
        $row      = count($data) + 3;
        $phpexcel   = new Spreadsheet();
        $sheet    = $phpexcel->getActiveSheet();
        $this->setSheet($phpexcel,$filename,$row);
        $sheet->fromArray($data, null, 'A2'); //想excel中写入数据
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($phpexcel, 'Xlsx');
        header("Pragma: public");
        header("Expires: 0");
        header("Content-Type:application/octet-stream");
        header("Content-Transfer-Encoding:binary");
        header('Cache-Control: max-age=0');
        header("Content-Disposition:attachment;filename=$filename");
        $writer->save('php://output');
        exit;
    }

    /**
     * 导出合同的数据
     */
    private function exportData($where)
    {
        if ($where['type']=='CHECKIN') {
            $data   = $this->exportCheckInData($where);
        } elseif($where['type']=='RESERVE') {
            $data   = $this->exportReserveData($where);
        } else {
            $data   = [];
        }
        return $data;
    }

    /**
     * 入住合同数据
     */
    private function exportCheckInData($where)
    {
        if ($where['time_type']=='BEGIN') {
            $residents   = Residentmodel::with('contract')
                ->with('roomunion')
                ->with('employee')
                ->with('store')
                ->where('store_id',$where['store_id'])
                ->where('contract_time','>',0)
                ->whereBetween('begin_time',[$where['time_start'],$where['time_end']])
                ->get();
        } elseif ($where['time_type']=='END') {
            $residents   = Residentmodel::with('contract')
                ->with('roomunion')
                ->with('employee')
                ->with('store')
                ->where('store_id',$where['store_id'])
                ->where('contract_time','>',0)
                ->whereBetween('end_time',[$where['time_start'],$where['time_end']])
                ->get();
        }
        $data   = [];
        foreach ($residents as $resident){
            if (empty($resident->contract->toArray())) {
                continue;
            }
            if($resident->contract[0]->status == 'GENERATED'){
                $status = '未签署';
            }elseif ($resident->contract[0]->status == 'SIGNING') {
                $status = '签属中';
            }elseif($resident->contract[0]->status == 'ARCHIVED'){
                $status = '签署完成';
            }else{
                $status = '未签署';
            }

            if($resident->contract[0]->view_url=='url_view'){
                $url='';
            }else{
                $url=$resident->contract[0]->view_url;
            }
            $data[] = [
                'contract_number'   => $resident->contract[0]->contract_id,
                'store_name'        => $resident->store->name,
                'room_address'      => $resident->roomunion->number,
                'area'              => $resident->roomunion->area,
                'resident_name'     => $resident->name,
                'rent_price'        => $resident->real_rent_money,
                'property_price'    => $resident->real_property_costs,
                'begin_time'        => $resident->begin_time->format('Y-m-d'),
                'end_time'          => $resident->end_time->format('Y-m-d'),
                'contract_time'     => $resident->contract_time,
                'pay_frequency'     => $resident->pay_frequency,
                'deposit_month'     => $resident->deposit_month,
                'book_money'        => $resident->book_money,
                'employee'          => $resident->employee->name,
                'status'            => $status,
                'url'               => $url,
            ];
        }

        return $data;
    }

    /**
     * 预定合同数据
     */
    private function exportReserveData($where)
    {
        if ($where['time_type']=='BEGIN') {
            $residents   = Residentmodel::with('reserve_contract')
                ->with('roomunion')
                ->with('employee')
                ->with('store')
                ->where('store_id',$where['store_id'])
                ->where('reserve_contract_time','>',0)
                ->whereBetween('reserve_begin_time',[$where['time_start'],$where['time_end']])
                ->get();
        } elseif ($where['time_type']=='END') {
            $residents   = Residentmodel::with('reserve_contract')
                ->with('roomunion')
                ->with('employee')
                ->with('store')
                ->where('store_id',$where['store_id'])
                ->where('reserve_contract_time','>',0)
                ->whereBetween('reserve_end_time',[$where['time_start'],$where['time_end']])
                ->get();
        }
        $data   = [];
        foreach ($residents as $resident){
            if (empty($resident->reserve_contract->toArray())) {
                continue;
            }
            if($resident->reserve_contract[0]->status == 'GENERATED'){
                $status = '未签署';
            }elseif ($resident->reserve_contract[0]->status == 'SIGNING') {
                $status = '签属中';
            }elseif($resident->reserve_contract[0]->status == 'ARCHIVED'){
                $status = '签署完成';
            }else{
                $status = '未签署';
            }

            if($resident->reserve_contract[0]->view_url=='url_view'){
                $url='';
            }else{
                $url=$resident->reserve_contract[0]->view_url;
            }
            $data[] = [
                'contract_number'   => $resident->reserve_contract[0]->contract_id,
                'store_name'        => $resident->store->name,
                'room_address'      => $resident->roomunion->number,
                'area'              => $resident->roomunion->area,
                'resident_name'     => $resident->name,
                'rent_price'        => $resident->rent_price,
                'property_price'    => $resident->property_price,
                'begin_time'        => $resident->reserve_begin_time->format('Y-m-d'),
                'end_time'          => $resident->reserve_end_time->format('Y-m-d'),
                'contract_time'     => $resident->reserve_contract_time,
                'pay_frequency'     => 0,
                'deposit_month'     => 0,
                'book_money'        => $resident->book_money,
                'employee'          => $resident->employee->name,
                'status'            => $status,
                'url'               => $url,
            ];
        }

        return $data;
    }



    /**
     * 设置导出表
     */
    private function setSheet(Spreadsheet $spreadsheet,$filename,$row)
    {
        $this->createPHPExcel($spreadsheet,$filename);
        $this->setExcelFirstRow($spreadsheet);
        $this->setExcelColumnWidth($spreadsheet); //设置Excel每列宽度
        return $spreadsheet;
    }

    /**
     * 导出合同的excel设置
     */
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

    private function setExcelFirstRow(Spreadsheet $phpexcel) {
        $phpexcel->getActiveSheet()->setCellValue('A1', '合同编号')
            ->setCellValue('B1', '门店名称')
            ->setCellValue('C1', '房间号')
            ->setCellValue('D1', '面积')
            ->setCellValue('E1', '住户姓名')
            ->setCellValue('F1', '租金')
            ->setCellValue('G1', '物业费')
            ->setCellValue('H1', '合同开始时间')
            ->setCellValue('I1', '合同结束时间')
            ->setCellValue('J1', '合同时长')
            ->setCellValue('K1', '支付周期')
            ->setCellValue('L1', '押金月份')
            ->setCellValue('M1', '定金')
            ->setCellValue('N1', '经办人')
            ->setCellValue('O1', '合同状态')
            ->setCellValue('P1', 'url地址');

    }

    private function setExcelColumnWidth(Spreadsheet $phpexcel) {
        $phpexcel->getActiveSheet()->getColumnDimension('A')->setWidth(22);
        $phpexcel->getActiveSheet()->getColumnDimension('B')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('C')->setWidth(12);
        $phpexcel->getActiveSheet()->getColumnDimension('D')->setWidth(12);
        $phpexcel->getActiveSheet()->getColumnDimension('E')->setWidth(12);
        $phpexcel->getActiveSheet()->getColumnDimension('F')->setWidth(12);
        $phpexcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('H')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('I')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('J')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('K')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('L')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('M')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('N')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('O')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('P')->setWidth(80);
    }

}
