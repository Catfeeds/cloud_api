<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Illuminate\Database\Capsule\Manager as DB;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/11 0011
 * Time:        9:07
 * Describe:    集中式房间管理
 */
class Roomunion extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 创建集中式房间
     */
    public function addUnion(){
        $field  = [
            'store_id','building_name','layer_total','layer_room_number',
            'contract_template_id','contract_min_time','contract_max_time',
            'deposit_type','pay_frequency_allow',
        ];

        $post   = $this->input->post(null,true);
        //验证基本信息
        if(!$this->validationText($this->validateUnionConfig())){
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        //验证付款周期
        $pay_frequency_allows = isset($post['pay_frequency_allow'])?$post['pay_frequency_allow']:null;
        if(!$pay_frequency_allows || !is_array($pay_frequency_allows)){
            $this->api_res(1002,['error'=>'允许的支付周期错误']);
            return;
        }
        foreach ($pay_frequency_allows as $pay_frequency_allow ){
            $a['pay_frequency_allow']   = $pay_frequency_allow;
            if(!$this->validationText($this->validatePayConfig(),$a)){
                $this->api_res(1002,['error'=>$this->form_first_error($field)]);
                return;
            }
        }
        //验证楼栋buildings格式
        $buildings = isset($post['buildings'])?$post['buildings']:null;
        if(!$buildings || !is_array($buildings)){
            $this->api_res(1002,['error'=>'请传入正确的楼栋格式']);
            return;
        }
        //验证楼栋信息 验证唯一
        $this->load->model('buildingmodel');
        $unique_building    = [];
        foreach ($buildings as $building){
            if(in_array($building['building_name'],$unique_building)){
                $this->api_res(1002,['error'=>'楼栋重复']);
                return;
            }
            if(!$this->validationText($this->validateBuildingConfig(),$building)){
                $this->api_res(1002,['error'=>$this->form_first_error($field)]);
                return;
            }
            if(Buildingmodel::where('store_id',$post['store_id'])->where('name',$building['building_name'])->first()){
                $this->api_res(1008);
                return;
            }
            $unique_building[]    = $building['building_name'];
        }

        //存入数据库
        $this->load->model('roomunionmodel');
        try{
            DB::beginTransaction();
            $store_id   = $post['store_id'];
            foreach ($buildings as $building){
                $db_building                    = new Buildingmodel();
                $db_building->store_id          = $store_id;
                $db_building->name              = $building['building_name'];
                $db_building->layer_total       = $building['layer_total'];
                $db_building->layer_room_number = $building['layer_room_number'];
                $a  = $db_building->save();
                $building_id    = $db_building->id;
                /*if(!$a){
                    DB::rollBack();
                    $this->api_res(1009);
                }*/
                $insert_room    = [];
                for($i=1;$i<=$building['layer_total'];$i++){
                    for($j=1;$j<=$building['layer_room_number'];$j++){
                        $insert_room[]  = [
                            'store_id'                 => $store_id,
                            'building_id'              => $building_id,
                            'building_name'            => $building['building_name'],
                            'layer_total'              => $building['layer_total'],
                            'layer'                    => $i,
                            'number'                   => sprintf('%02d%02d',$i,$j),
                            'contract_template_id'     => $post['contract_template_id'],
                            'contract_min_time'        => $post['contract_min_time'],
                            'contract_max_time'        => $post['contract_max_time'],
                            'deposit_type'             => $post['deposit_type'],
                            'pay_frequency_allow'      => json_encode($post['pay_frequency_allow']),
                            'created_at'               => date('Y-m-d H:i:s',time()),
                            'updated_at'               => date('Y-m-d H:i:s',time()),
                        ];
                    }
                }
                $b  = Roomunionmodel::insert($insert_room);
                if(!$a || !$b){
                    DB::rollBack();
                    $this->api_res(1009);
                    return;
                }
            }
            DB::commit();
            $this->api_res(0);
        }catch (Exception $e){
            DB::rollBack();
            throw $e;
        }
    }


    /**
     * 批量更新集中式房间
     */
    public function batchUpdateUnion(){
        $field  = ['store_id','building_id','contract_template_id','contract_min_time',
            'contract_max_time','deposit_type','pay_frequency_allow'];
        $post   = $this->input->post(null,true);
        //验证基本信息
        if(!$this->validationText($this->validateBatchUnionConfig())){
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        //验证付款周期
        $pay_frequency_allows = isset($post['pay_frequency_allow'])?$post['pay_frequency_allow']:null;
        if(!$pay_frequency_allows || !is_array($pay_frequency_allows)){
            $this->api_res(1002,['error'=>'允许的支付周期错误']);
            return;
        }
        foreach ($pay_frequency_allows as $pay_frequency_allow ){
            $a['pay_frequency_allow']   = $pay_frequency_allow;
            if(!$this->validationText($this->validatePayConfig(),$a)){
                $this->api_res(1002,['error'=>$this->form_first_error($field)]);
                return;
            }
        }
        $this->load->model('roomunionmodel');
        $rooms  = Roomunionmodel::where(['store_id'=>$post['store_id'],'building_id'=>$post['building_id']]);
        $updates    = [
            'contract_template_id'  => $post['contract_template_id'],
            'contract_min_time'     => $post['contract_min_time'],
            'contract_max_time'     => $post['contract_max_time'],
            'deposit_type'          => $post['deposit_type'],
            'pay_frequency_allow'   => json_encode($post['pay_frequency_allow']),
        ];
        if($rooms->update($updates)){
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }
    }

    /**
     * 获取门店下的楼栋信息
     */
    public function showBuilding(){
        $post   = $this->input->post(null,true);
        isset($post['store_id'])?$where['store_id']=intval(strip_tags(trim($post['store_id']))):$where=[];
        if(!$where){
            $this->api_res(0,['buildings'=>[]]);
            return;
        }
        $this->load->model('roomunionmodel');
        $buildings  = Roomunionmodel::select(['store_id','building_id','building_name'])->groupBy('building_id')->get();
        $this->api_res(0,['buildings'=>$buildings]);
    }



    /**
     * 集中式房间列表
     */
    public function listUnion(){
        $field  = ['boss_room_union.id as room_id','boss_room_union.store_id','boss_store.name as store_name','boss_room_union.building_name','boss_store.province','boss_store.city',
            'boss_store.district','boss_store.address','boss_room_union.rent_price','boss_room_union.property_price',
            'boss_room_union.keeper','boss_room_union.status','boss_room_type.name as room_type_name'
            ];
        $post   = $this->input->post(null,true);
        $page   = intval(isset($post['page'])?intval(strip_tags(trim($post['page']))):1);
        $offset = PAGINATE*($page-1);
        $where  = [];
        (isset($post['store_id'])&&!empty($post['store_id']))?$where['boss_room_union.store_id']=$post['store_id']:null;
        (isset($post['building_name'])&&!empty($post['building_name']))?$where['boss_room_union.building_name']=$post['building_name']:null;
        $this->load->model('roomunionmodel');
        $count  = ceil(Roomunionmodel::where($where)->count()/PAGINATE);
        if($page>$count){
            $this->api_res(0,['count'=>$count,'rooms'=>[]]);
            return;
        }
        $rooms  = Roomunionmodel::leftJoin('boss_store','boss_store.id','=','boss_room_union.store_id')
            ->leftJoin('boss_room_type','boss_room_type.id','boss_room_union.room_type_id')
            ->select($field)->offset($offset)->limit(PAGINATE)->orderBy('boss_room_union.id')->where($where)
            ->get();
        $this->api_res(0,['count'=>$count,'rooms'=>$rooms]);
    }

    /**
     * 查看集中式房间信息
     */
    public function getUnion(){
        $field  = ['store_id','room_type_id','layer','layer_total','rent_price','property_price',
            'contract_template_id','contract_min_time','contract_max_time','deposit_type','pay_frequency_allow'];
        $post   = $this->input->post(null,true);
        $room_id    = isset($post['room_id'])?intval(strip_tags(trim($post['room_id']))):null;
        if(!$room_id){
            $this->api_res(1005);
            return;
        }
        //需要关联的门店,房型,合同模板
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $this->load->model('roomtypemodel');
        $this->load->model('contracttemplatemodel');
        $room   = Roomunionmodel::with('store')->with('roomtype')->with('template')->select($field)->find($room_id);
        if(!$room){
            $this->api_res(1007);
        }else{
            $this->api_res(0,['room'=>$room]);
        }
    }

    /**
     * 验证支付周期
     */
    public function validatePayConfig(){
        $config = [
            array(
                'field' => 'pay_frequency_allow',
                'label' => '允许的支付周期',
                'rules' => 'trim|required|integer|in_list[1,2,3,6,12,24]'
            ),
        ];
        return $config;
    }

    /**
     * 创建集中式房间验证规则
     */
    public function validateUnionConfig(){
        $config = [
            array(
                'field' => 'store_id',
                'label' => '门店id',
                'rules' => 'trim|required|integer'
            ),
            array(
                'field' => 'contract_template_id',
                'label' => '选择合同模板',
                'rules' => 'trim|required|integer'
            ),
            array(
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
            ),
        ];
        return $config;
    }

    /**
     * 批量编辑集中式验证规则
     */
    public function validateBatchUnionConfig(){
        $config = [
            array(
                'field' => 'store_id',
                'label' => '门店id',
                'rules' => 'trim|required|integer'
            ),
            array(
                'field' => 'building_id',
                'label' => '楼栋id',
                'rules' => 'trim|integer|required'
            ),
            array(
                'field' => 'contract_template_id',
                'label' => '选择合同模板',
                'rules' => 'trim|required|integer'
            ),
            array(
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
            ),
        ];
        return $config;
    }

    /**
     * 新建验证集中式楼栋
     */
    public function validateBuildingConfig(){
        $config = [
            array(
                'field' => 'building_name',
                'label' => '楼栋名称',
                'rules' => 'trim|required'
            ),
            array(
                'field' => 'layer_total',
                'label' => '总楼层',
                'rules' => 'trim|required|integer'
            ),
            array(
                'field' => 'layer_room_number',
                'label' => '每层房间数',
                'rules' => 'trim|required|integer'
            ),
        ];
        return $config;
    }


}