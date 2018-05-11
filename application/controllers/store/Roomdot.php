<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Illuminate\Database\Capsule\Manager as DB;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/7 0007
 * Time:        14:14
 * Describe:    分布式房间管理
 */
class Roomdot extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 创建分布式房间
     */
    public function addDot(){
        $field  = ['store_id','community_id','building_name','unit','number','layer','layer_total','room_number',
            'hall_number','toilet_number','area','contract_template_id','contract_min_time','contract_max_time',
            'deposit_type','pay_frequency_allow','the_room_number','room_area','room_toward','room_feature','room_provides',
        ];
        $post   = $this->input->post(null,true);
        //验证房屋
        if(!$this->validationText($this->validateHouseConfig())){
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $images = isset($post['images'])?$post['images']:null;
        if(!$images || !is_array($images)){
            $this->api_res(1002,['error'=>'房屋图片不能为空']);
            return;
        }
        $pay_frequency_allows = isset($post['pay_frequency_allow'])?$post['pay_frequency_allow']:null;
        if(!$pay_frequency_allows || !is_array($pay_frequency_allows)){
            $this->api_res(1002,['error'=>'允许的支付周期错误']);
            return;
        }
        //遍历验证支付周期
        foreach ($pay_frequency_allows as $pay_frequency_allow ){
            $a['pay_frequency_allow']   = $pay_frequency_allow;
            if(!$this->validationText($this->validatePayConfig(),$a)){
                $this->api_res(1002,['error'=>$this->form_first_error($field)]);
                return;
            }
        }
        //遍历验证房间
        $rooms   = isset($post['rooms'])?$post['rooms']:null;
        if(!$rooms || !is_array($rooms)){
            $this->api_res(1002,['error'=>'房间信息不能为空或字符串']);
            return;
        }
        if($post['room_number']!=count($rooms)){
            $this->api_res(1002,['error'=>'房间数目不匹配']);
            return;
        }
        for($i=0;$i<count($rooms);$i++){
            if(!$rooms[$i] || !is_array($rooms[$i])){
                $this->api_res(1002,['error'=>'房间信息不能为空或字符串']);
                return;
            }
            if(!$this->validationText($this->validateDotConfig(),$rooms[$i])){
                $this->api_res(1002,['error'=>$this->form_first_error($field)]);
                return;
            }
        }
        //return;
        //加载房屋和房间模型
        $this->load->model('housemodel');
        $this->load->model('roomdotmodel');
        //判断该小区是否存在该房间
        $where['store_id']      = $post['store_id'];
        $where['community_id']  = $post['community_id'];
        $where['building_name'] = $post['building_name'];
        $where['unit']          = $post['unit'];
        $where['layer']         = $post['layer'];
        $where['number']        = $post['number'];
        if(Housemodel::where($where)->first()){
            $this->api_res(1008);
            return;
        }
        //保存到数据库
        $house  = new Housemodel();
        $room   = new Roomdotmodel();
        try
        {
            DB::beginTransaction();
            //存入房屋信息
            $house->fill($post);
            $house->images  = json_encode($this->splitAliossUrl($images,true));
            $a  = $house->save();
            //存入房间信息
            $store_id       = $post['store_id'];
            $community_id   = $post['community_id'];
            $house_id       = $house->id;
            $room_insert    = [];
            $i              = 1;
            foreach ($rooms as $room_item) {
                $k  = &$i;
                $room_insert[]      = [
                    'store_id'      => $store_id,
                    'community_id'  => $community_id,
                    'house_id'      => $house_id,
                    'number'        => $room_item['the_room_number'],
                    'area'          => $room_item['room_area'],
                    'toward'        => $room_item['room_toward'],
                    'feature'       => $room_item['room_feature'],
                    'provides'      => $room_item['room_provides'],
                    'contract_template_id'  => $post['contract_template_id'],
                    'contract_min_time'     => $post['contract_min_time'],
                    'contract_max_time'     => $post['contract_max_time'],
                    'deposit_type'          => $post['deposit_type'],
                    'pay_frequency_allow'   => json_encode($post['pay_frequency_allow']),
                    'created_at'            => date('Y-m-d H:i:s',time()),
                    'updated_at'            => date('Y-m-d H:i:s',time()),
                    'sort'          => $k++,
                ];
            }
            $b  = $room->insert($room_insert);
            if($a && $b){
                DB::commit();
                $this->api_res(0);
            }else{
                DB::rollBack();
                $this->api_res(1009);
            }
        }catch (Exception $e)
        {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * 批量编辑分布式房间
     */
    public function batchUpdateDot(){
        $field  = ['store_id','community_id','contract_template_id','contract_min_time',
            'contract_max_time','deposit_type','pay_frequency_allow'];
        $post   = $this->input->post(null,true);
        //验证基本信息
        if(!$this->validationText($this->validateBatchDotConfig())){
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
        $this->load->model('roomdotmodel');
        $rooms  = Roomdotmodel::where(['store_id'=>$post['store_id'],'community_id'=>$post['community_id']]);
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
     * 分布式房间列表
     */
    public function listDot(){
        $field  = ['id','room_type_id','layer','number',];
        $post   = $this->input->post(null,true);
        $page   = intval(isset($post['page'])?$post['page']:1);
        $offset = PAGINATE*($page-1);
        isset($post['store_id'])?$where['store_id']=$post['store_id']:null;
        isset($post['building_name'])?$where['building_name']=$post['building_name']:null;
        $this->load->model('roomdotmodel');
        $rooms  = Roomdotmodel::get($field);


    }



    /**
     * 查看分布式房间信息
     */
    public function getDot(){
        $field  = ['store_id','community_id','house_id','rent_price','property_price','sort','contract_template_id',
            'contract_min_time','contract_max_time','deposit_type','pay_frequency_allow'];
        $post   = $this->input->post(null,true);
        $room_id    = isset($post['room_id'])?intval(strip_tags(trim($post['room_id']))):null;
        if(!$room_id){
            $this->api_res(1005);
            return;
        }
        //需要关联的 房屋 门店 合同模板
        $this->load->model('roomdotmodel');
        $this->load->model('storemodel');
        $this->load->model('communitymodel');
        $this->load->model('housemodel');
        $this->load->model('contracttemplatemodel');
        $room   = Roomdotmodel::with('store')->with('house')->with('community')->select($field)->find($room_id);
        if(!$room){
            $this->api_res(1007);
        }else{
            $all_room   = Roomdotmodel::where('house_id',$room->house_id)->with('template')->orderBy('sort')->get($field);
            $room['rooms']  = $all_room;
            $this->api_res(0,['room'=>$room]);
        }
    }

    /**
     * 添加分布式房间的验证规则
     */
    public function validateHouseConfig(){
        $config = [
            array(
                'field' => 'store_id',
                'label' => '门店id',
                'rules' => 'trim|required|integer'
            ),
            array(
                'field' => 'community_id',
                'label' => '小区id',
                'rules' => 'trim|required|integer'
            ),
            array(
                'field' => 'building_name',
                'label' => '楼栋名称',
                'rules' => 'trim|required'
            ),
            array(
                'field' => 'unit',
                'label' => '单元号',
                'rules' => 'trim|required|integer'
            ),
            array(
                'field' => 'layer',
                'label' => '所在楼层',
                'rules' => 'trim|required|integer'
            ),
            array(
                'field' => 'layer_total',
                'label' => '总楼层',
                'rules' => 'trim|required|integer'
            ),
            array(
                'field' => 'number',
                'label' => '房屋号',
                'rules' => 'trim|required'
            ),
            array(
                'field' => 'room_number',
                'label' => '房间数量',
                'rules' => 'trim|required|integer'
            ),
            array(
                'field' => 'hall_number',
                'label' => '客厅数量',
                'rules' => 'trim|required|integer'
            ),
            array(
                'field' => 'toilet_number',
                'label' => '卫生间数量',
                'rules' => 'trim|required|integer'
            ),
            array(
                'field' => 'area',
                'label' => '房屋面积',
                'rules' => 'trim|required|numeric'
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
     * 验证分布式房间信息
     */
    public function validateDotConfig(){
        $config = [
            array(
                'field' => 'the_room_number',
                'label' => '房间号',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'room_area',
                'label' => '房间面积',
                'rules' => 'required|trim|numeric',
            ),
            array(
                'field' => 'room_toward',
                'label' => '房间朝向',
                'rules' => 'required|trim|in_list[E,W,S,N,EW,SN]',
            ),
            array(
                'field' => 'room_feature',
                'label' => '房间特色',
                'rules' => 'required|trim|in_list[M,S,MT]',
            ),
            array(
                'field' => 'room_provides',
                'label' => '房间配套',
                'rules' => 'required|trim',
            ),
        ];
        return $config;
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
     * 批量编辑分布式验证规则
     */
    public function validateBatchDotConfig(){
        $config = [
            array(
                'field' => 'store_id',
                'label' => '门店id',
                'rules' => 'trim|required|integer'
            ),
            array(
                'field' => 'community_id',
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

}
