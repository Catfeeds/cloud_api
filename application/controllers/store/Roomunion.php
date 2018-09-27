<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Illuminate\Database\Capsule\Manager as DB;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/11 0011
 * Time:        9:07
 * Describe:    集中式房间管理
 */
class Roomunion extends MY_Controller {
    public function __construct() {
        parent::__construct();
    }

    /**
     * 创建集中式房间
     */
    public function addUnion() {
        $field = [
            'store_id', 'building_name', 'layer_total', 'layer_room_number',
            'contract_template_long_id', 'contract_template_short_id', 'contract_template_reserve_id',
        ];

        $post = $this->input->post(null, true);
        //验证基本信息
        if (!$this->validationText($this->validateUnionConfig())) {
            $this->api_res(1002, ['error' => $this->form_first_error($field)]);
            return;
        }
        //验证楼栋buildings格式
        $buildings = isset($post['buildings']) ? $post['buildings'] : null;
        if (!$buildings || !is_array($buildings)) {
            $this->api_res(1002, ['error' => '请传入正确的楼栋格式']);
            return;
        }
        //验证楼栋信息 验证唯一
        $this->load->model('buildingmodel');
        $unique_building = [];
        foreach ($buildings as $building) {
            if (in_array($building['building_name'], $unique_building)) {
                $this->api_res(1002, ['error' => '楼栋重复']);
                return;
            }
            if (!$this->validationText($this->validateBuildingConfig(), $building)) {
                $this->api_res(1002, ['error' => $this->form_first_error($field)]);
                return;
            }
            if (Buildingmodel::where('store_id', $post['store_id'])->where('name', $building['building_name'])->first()) {
                $this->api_res(1008);
                return;
            }
            $unique_building[] = $building['building_name'];
        }

        //存入数据库
        $this->load->model('roomunionmodel');
        try {
            DB::beginTransaction();
            $store_id = $post['store_id'];
            foreach ($buildings as $building) {
                $db_building                    = new Buildingmodel();
                $db_building->store_id          = $store_id;
                $db_building->name              = $building['building_name'];
                $db_building->layer_total       = $building['layer_total'];
                $db_building->layer_room_number = $building['layer_room_number'];
                $a                              = $db_building->save();
                $building_id                    = $db_building->id;
                $insert_room                    = [];
                for ($i = 1; $i <= $building['layer_total']; $i++) {
                    for ($j = 1; $j <= $building['layer_room_number']; $j++) {
                        $insert_room[] = [
                            'store_id'                     => $store_id,
                            'building_id'                  => $building_id,
                            'building_name'                => $building['building_name'],
                            'layer_total'                  => $building['layer_total'],
                            'layer'                        => $i,
                            'number'                       => sprintf('%02d%02d', $i, $j),
                            'contract_template_short_id'   => $post['contract_template_short_id'],
                            'contract_template_long_id'    => $post['contract_template_long_id'],
                            'contract_template_reserve_id' => $post['contract_template_reserve_id'],
                            'created_at'                   => date('Y-m-d H:i:s', time()),
                            'updated_at'                   => date('Y-m-d H:i:s', time()),
                        ];
                    }
                }
                $b = Roomunionmodel::insert($insert_room);
                if (!$a || !$b) {
                    DB::rollBack();
                    $this->api_res(1009);
                    return;
                }
            }
            DB::commit();
            $this->api_res(0);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 批量更新集中式房间
     */
    public function batchUpdateUnion() {
        $field = ['store_id', 'building_id', 'room_type_id',
            'contract_template_long_id', 'contract_template_short_id', 'contract_template_reserve_id',
        ];
        $post = $this->input->post(null, true);
        //验证基本信息
        if (!$this->validationText($this->validateBatchUnionConfig())) {
            $this->api_res(1002, ['error' => $this->form_first_error($field)]);
            return;
        }

        $this->load->model('roomunionmodel');
        $rooms = Roomunionmodel::where(['store_id' => $post['store_id'], 'building_id' => $post['building_id']])->first();
        if (!$rooms) {
            $this->api_res(1007);
            return;
        }
        $room_type_id = $post['room_type_id'];
        $this->load->model('roomtypemodel');
        $provides = Roomtypemodel::find($room_type_id)->provides;
        $updates  = [
            'contract_template_short_id'   => $post['contract_template_short_id'],
            'contract_template_long_id'    => $post['contract_template_long_id'],
            'contract_template_reserve_id' => $post['contract_template_reserve_id'],
            'room_type_id'                 => $room_type_id,
            'provides'                     => $provides,
            'updated_at'                   => date('Y-m-d H:i:s', time()),
        ];
        if ($rooms->update($updates)) {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }
    }

    /**
     * 获取门店下的楼栋信息
     */
    public function showBuilding() {
        $post                                         = $this->input->post(null, true);
        isset($post['store_id']) ? $where['store_id'] = intval(strip_tags(trim($post['store_id']))) : $where = [];
        if (!$where) {
            $this->api_res(0, ['buildings' => []]);
            return;
        }
        $this->load->model('roomunionmodel');
        $buildings = Roomunionmodel::where($where)->select(['store_id', 'building_id', 'building_name'])->groupBy('building_id')->get();
        $this->api_res(0, ['buildings' => $buildings]);
    }
	
    /**
     * 房间列表
     */
    public function listRoom() {
        $field = [
        	'boss_store.province', 'boss_store.city','boss_store.district',
	        'boss_store.name as store_name','boss_store.address','boss_store.rent_type',
            'boss_community.name as community_name','boss_house.building_name','boss_house.unit',
            'boss_room_union.id as room_id', 'boss_room_union.number as room_number',
            'boss_room_union.store_id','boss_room_type.name as room_type_name',
            'boss_room_union.status', 'boss_room_union.rent_price', 'boss_room_union.property_price',
            'boss_house.layer','boss_house.number', 'boss_room_union.feature', 'boss_room_union.keeper',
	        ];
        $post      = $this->input->post(null, true);
        $page      = isset($post['page']) ? intval(strip_tags(trim($post['page']))) : 1;
        $offset    = PAGINATE * ($page - 1);
        $where     = [];
        $store_ids = explode(',', $this->employee->store_ids);
        (isset($post['store_id']) && !empty($post['store_id'])) ? $where['boss_room_union.store_id'] = intval(strip_tags(trim($post['store_id']))) : null;
        (isset($post['community_id']) && !empty($post['community_id'])) ? $where['boss_room_union.community_id'] = intval(strip_tags(trim($post['community_id']))) : null;
        $search = isset($post['search']) ? $post['search'] : '';
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        if ($search) {
            $store_ids = Storemodel::where('district', 'like', "%$search%")
                ->orWhere('address', 'like', "%$search%")
                ->get(['id'])
                ->map(function ($a) {
                    return $a->id;
                });
            $count = ceil(Roomunionmodel::whereIn('store_id', $store_ids)
                    ->orWhere('number', 'like', "%$search%")
                    ->where($where)
                    ->count() / PAGINATE);
            if ($page > $count) {
                $this->api_res(0, ['count' => $count, 'rooms' => []]);
                return;
            }
            $rooms = Roomunionmodel::leftJoin('boss_store', 'boss_store.id', '=', 'boss_room_union.store_id')
                ->leftJoin('boss_room_type', 'boss_room_type.id', '=', 'boss_room_union.room_type_id')
	            ->leftJoin('boss_community', 'boss_community.id', '=', 'boss_room_union.community_id')
	            ->leftJoin('boss_house', 'boss_house.id', '=', 'boss_room_union.house_id')
	            ->select($field)
                ->offset($offset)
                ->limit(PAGINATE)
                ->orderBy('boss_room_union.id','number')
                ->whereIn('boss_room_union.store_id', $store_ids)
                ->where($where)
                ->orWhere(function ($query) use ($search) {
                    $query->where('boss_room_union.number', 'like', "%$search%");
                })
                ->get()->map(function ($s){
		            //处理分布式数据展示
		            if($s->rent_type == 'DOT'){
			            //楼栋
			            if (isset($s->building_name)&&!empty($s->building_name)){
				            $building_name = $s->building_name."(栋)";
			            }else{
				            $building_name = '';
			            }
			            //单元
			            if (isset($s->unit)&&!empty($s->unit)){
				            $unit = $s->unit."(单元)";
			            }else{
				            $unit = '';
			            }
			            $s->store_name      = $s->store_name.$building_name.$unit.$s->number;
			            $s->room_type_name  = $this->feature($s->feature);
		            }
		            return $s;
	            });
            $this->api_res(0, ['count' => $count, 'rooms' => $rooms]);
            return;
        }

        $count = ceil(Roomunionmodel::where($where)->whereIn('store_id', $store_ids)->count() / PAGINATE);
        if ($page > $count) {
            $this->api_res(0, ['count' => $count, 'rooms' => []]);
            return;
        }
        $rooms = Roomunionmodel::leftJoin('boss_store', 'boss_store.id', '=', 'boss_room_union.store_id')
            ->leftJoin('boss_room_type', 'boss_room_type.id', '=', 'boss_room_union.room_type_id')
	        ->leftJoin('boss_community', 'boss_community.id', '=', 'boss_room_union.community_id')
	        ->leftJoin('boss_house', 'boss_house.id', '=', 'boss_room_union.house_id')
            ->select($field)->offset($offset)->limit(PAGINATE)->orderBy('boss_room_union.id','number')
            ->where($where)->whereIn('boss_room_union.store_id', $store_ids)
            ->get()->map(function ($s){
            	//处理分布式数据展示
            	if($s->rent_type == 'DOT'){
		            //楼栋
            		if (isset($s->building_name)&&!empty($s->building_name)){
			            $building_name = $s->building_name."(栋)";
		            }else{
			            $building_name = '';
		            }
		            //单元
		            if (isset($s->unit)&&!empty($s->unit)){
			            $unit = $s->unit."(单元)";
		            }else{
			            $unit = '';
		            }
		            $s->store_name      = $s->store_name.$building_name.$unit.$s->number;
	                $s->room_type_name  = $this->feature($s->feature);
            	}
		        return $s;
	        });
        $this->api_res(0, ['count' => $count, 'rooms' => $rooms]);
    }
    
    private function feature($s)
    {
	    switch ($s){
		    case 'M':
			    return '主卧';
			    break;
		    case 'S':
			    return '次卧';
			    break;
		    case 'MT':
			    return '独卫主卧';
			    break;
		    default:
			    return '';
			    break;
	    }
    }
    
    /**
     * 查看集中式房间信息
     */
    public function getUnion() {
        $field = ['id', 'store_id', 'room_type_id', 'layer', 'area', 'layer_total', 'rent_price', 'property_price', 'provides',
            'contract_template_long_id', 'contract_template_short_id', 'contract_template_reserve_id','community_id','feature','house_id'
//            contract_min_time','contract_max_time','deposit_type','pay_frequency_allow'
        ];
        $post    = $this->input->post(null, true);
        $room_id = isset($post['room_id']) ? intval(strip_tags(trim($post['room_id']))) : null;
        if (!$room_id) {
            $this->api_res(1005);
            return;
        }
        //需要关联的门店,房型,合同模板
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $this->load->model('roomtypemodel');
        $this->load->model('contracttemplatemodel');
        $this->load->model('communitymodel');
        $this->load->model('housemodel');
        $room = Roomunionmodel::with('store')
            ->with('roomtype')
            ->with('community')
            ->with('house')
            ->with('long_template')
            ->with('short_template')
            ->with('reserve_template')
            ->select($field)->find($room_id);
        if($room->store->rent_type=='UNION'){
            $room->roomtype->description = strip_tags(htmlspecialchars_decode($room->roomtype->description));
        }else{
            $room->community->describe  = strip_tags(htmlspecialchars_decode($room->community->describe));
        }

        if (!$room) {
            $this->api_res(1007);
        } else {
            $this->api_res(0, ['room' => $room]);
        }
    }

    /**
     * 提交查看房间信息时修改的内容
     */
    public function submitUnion() {
        $field = ['room_id', 'provides',
//            'contract_template_long_id', 'contract_template_short_id', 'contract_template_reserve_id',
//            'contract_min_time','contract_max_time','deposit_type','pay_frequency_allow'
        ];
        if (!$this->validationText($this->validateSubmitUnion())) {
            $this->api_res(1002, ['error' => $this->form_first_error($field)]);
            return;
        }
        $post = $this->input->post(null, true);
        $this->load->model('roomunionmodel');
        $room = Roomunionmodel::find($post['room_id']);
        if (!$room) {
            $this->api_res(1007);
            return;
        }
//        $room->contract_template_long_id    = $post['contract_template_long_id'];
//        $room->contract_template_short_id   = $post['contract_template_short_id'];
//        $room->contract_template_reserve_id = $post['contract_template_reserve_id'];
        $room->provides                     = $post['provides'];
        if ($room->save()) {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }
    }
    /*
        * 获取集中式房源导入模版
        * */
    public function getUnionTemplate(){
        $data = Array();
        $objPHPExcel = new Spreadsheet();
        $sheet       = $objPHPExcel->getActiveSheet();
        $i           = 1;
        $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, '门店名称');
        $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, '房型');
        $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, '房间号');
        $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, '租金');
        $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, '物业费');
        $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, '热水单价');
        $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, '冷水单价');
        $objPHPExcel->getActiveSheet()->setCellValue('H' . $i, '用电单价');
        $objPHPExcel->getActiveSheet()->setCellValue('I' . $i, '面积');
        $objPHPExcel->getActiveSheet()->setCellValue('J' . $i, '所在层');
        $sheet->fromArray($data, null, 'A2');
        $writer = new Xlsx($objPHPExcel);
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-excel");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header("Content-Disposition:attachment;filename='集中式房间模版.xlsx'");
        header("Content-Transfer-Encoding:binary");
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
    private function setExcelTitle(Spreadsheet $phpexcel, $store) {
        $phpexcel->getActiveSheet()
            ->mergeCells('A1:O2')
            ->setCellValue('A1', "$store" . '房间导入')
            ->getStyle("A1:O2")
            ->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $phpexcel->getActiveSheet()->getCell('A1')->getStyle()->getFont()->setSize(16);
    }

    private function setExcelColumnWidth(Spreadsheet $phpexcel) {
        $phpexcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $phpexcel->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $phpexcel->getActiveSheet()->getColumnDimension('C')->setWidth(12);
        $phpexcel->getActiveSheet()->getColumnDimension('D')->setWidth(12);
        $phpexcel->getActiveSheet()->getColumnDimension('E')->setWidth(12);
        $phpexcel->getActiveSheet()->getColumnDimension('F')->setWidth(12);
        $phpexcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('H')->setWidth(10);
    }
    private function setAlignCenter(Spreadsheet $phpexcel, $row) {
        $phpexcel->getActiveSheet()
            ->getStyle("A3:N{$row}")
            ->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    }

    private function setExcelFirstRow(Spreadsheet $phpexcel) {
        $phpexcel->getActiveSheet()->setCellValue('A3' , '门店名称')
        ->setCellValue('B3' , '房型')
        ->setCellValue('C3' , '房间号')
        ->setCellValue('D3' , '租金')
        ->setCellValue('E3' , '物业费')
        ->setCellValue('F3' , '热水单价')
        ->setCellValue('G3' , '冷水单价')
        ->setCellValue('H3' , '用电单价')
        ->setCellValue('I3' , '面积')
        ->setCellValue('J3' , '所在层');
    }
    /*
     * 批量导入房间数据
     * */
    public function importRoomUnion(){
        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');
        $this->load->model('roomtypemodel');
        $this->load->model('buildingmodel');
        $store_id = $this->input->post('store_id', true);
        if(!$store_id){
            $this->api_res(1002);
            return false;
        }
        $sheetArray = $this->uploadOssSheet();
        $roomunion = new Roomunionmodel();
        $data    = $roomunion->checkAndGetInputData($sheetArray, $store_id);
        if(!empty($data)){
            $this->api_res(10052,['error'=>$data]);
            return;
        }
        $res        = $roomunion->writeReading($sheetArray, $store_id);
        if (!empty($res)){
            $this->api_res(10051,['error'=>$res]);
        }else{
            $this->api_res(0);
        }
    }

    /**
     * 转换表读数为数组
     * @return array
     */
    private function uploadOssSheet() {
        $url       = $this->input->post('url');
        $f_open    = fopen($url, 'r');
        $file_name = APPPATH . 'cache/test.xlsx';
        file_put_contents($file_name, $f_open);
        $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($file_name);
        $reader        = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
        $reader->setReadDataOnly(true);
        $excel = $reader->load($file_name);
        $sheet = $excel->getActiveSheet()->toArray();
        array_shift($sheet);
        return $sheet;
    }

    /**
     * 批量删除集中式房间
     */
    public function destroy() {
        $id = $this->input->post('room_id', true);
        if (!is_array($id)) {
            $this->api_res(1005);
            return;
        }
        $this->load->model('roomunionmodel');
        if (Roomunionmodel::destroy($id)) {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }
    }

    private function validateSubmitUnion() {
        return array(
//            array(
//                'field' => 'contract_template_long_id',
//                'label' => '选择长租合同模板',
//                'rules' => 'trim|required|integer',
//            ),
//            array(
//                'field' => 'contract_template_short_id',
//                'label' => '选择短租合同模板',
//                'rules' => 'trim|required|integer',
//            ),
//            array(
//                'field' => 'contract_template_reserve_id',
//                'label' => '选择预定合同模板',
//                'rules' => 'trim|required|integer',
//            ),
            array(
                'field' => 'room_id',
                'label' => '房间id',
                'rules' => 'trim|required|integer',
            ),
            array(
                'field' => 'provides',
                'label' => '房型设施',
                'rules' => 'trim',
            ),
        );
    }

    /**
     * 验证支付周期
     */
    public function validatePayConfig() {
        $config = [
            array(
                'field' => 'pay_frequency_allow',
                'label' => '允许的支付周期',
                'rules' => 'trim|required|integer|in_list[1,2,3,6,12,24]',
            ),
        ];
        return $config;
    }

    /**
     * 创建集中式房间验证规则
     */
    public function validateUnionConfig() {
        $config = [
            array(
                'field' => 'store_id',
                'label' => '门店id',
                'rules' => 'trim|required|integer',
            ),
            array(
                'field' => 'contract_template_long_id',
                'label' => '选择长租合同模板',
                'rules' => 'trim|required|integer',
            ),
            array(
                'field' => 'contract_template_short_id',
                'label' => '选择短租合同模板',
                'rules' => 'trim|required|integer',
            ),
            array(
                'field' => 'contract_template_reserve_id',
                'label' => '选择预定合同模板',
                'rules' => 'trim|required|integer',
            ),
            /*array(
        'field' => 'contract_min_time',
        'label' => '合同最少签约期限（以月份计）',
        'rules' => 'trim|required|integer'
        ),
        array(
        'field' => 'contract_max_time',
        'label' => '合同最多签约期限（以月份计）',
        'rules' => 'trim|required|integer'
        ),
        array(
        'field' => 'deposit_type',
        'label' => '押金信息',
        'rules' => 'trim|required|in_list[FREE]'
        ),*/
        ];
        return $config;
    }

    /**
     * 批量编辑集中式验证规则
     */
    public function validateBatchUnionConfig() {
        $config = [
            array(
                'field' => 'store_id',
                'label' => '门店id',
                'rules' => 'trim|required|integer',
            ),
            array(
                'field' => 'room_type_id',
                'label' => '房型ID',
                'rules' => 'trim|required|integer',
            ),
            array(
                'field' => 'building_id',
                'label' => '楼栋id',
                'rules' => 'trim|integer|required',
            ),
            array(
                'field' => 'contract_template_long_id',
                'label' => '选择长租合同模板',
                'rules' => 'trim|required|integer',
            ),
            array(
                'field' => 'contract_template_short_id',
                'label' => '选择短租合同模板',
                'rules' => 'trim|required|integer',
            ),
            array(
                'field' => 'contract_template_reserve_id',
                'label' => '选择预定合同模板',
                'rules' => 'trim|required|integer',
            ),
        ];
        return $config;
    }

    /**
     * 新建验证集中式楼栋
     */
    public function validateBuildingConfig() {
        $config = [
            array(
                'field' => 'building_name',
                'label' => '楼栋名称',
                'rules' => 'trim|required|alpha_numeric',
            ),
            array(
                'field' => 'layer_total',
                'label' => '总楼层',
                'rules' => 'trim|required|integer',
            ),
            array(
                'field' => 'layer_room_number',
                'label' => '每层房间数',
                'rules' => 'trim|required|integer',
            ),
        ];
        return $config;
    }

}
