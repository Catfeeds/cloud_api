<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Illuminate\Database\Capsule\Manager as DB;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/7 0007
 * Time:        14:14
 * Describe:    房间管理
 */
class Room extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 添加分布式房间
     */
    public function addDot(){
        //加载房屋和房间模型
        $this->load->model('housemodel');
        $this->load->model('roomdotmodel');
        //表单验证的字段
        $field  = [];
        if($this->validationText($this->validateDotConfig())){
            $this->api_res(1002,$this->form_first_error($field));
            return;
        }
        $post   = $this->input->post(null,true);
        $where['store_id']      = $post['store_id'];
        $where['community_id']  = $post['community_id'];
        //判断该小区是否存在该房间
        if(Housemodel::where($where)->first()){
            $this->api_res(1008);
            return;
        }
        $house  = new Housemodel();
        $room   = new Roomdotmodel();
        try
        {
            DB::beginTransaction();
            $house->store_id    = $post['store_id'];
            $house->community_id    = $post['community_id'];
            $a  = $house->save();
            $room->house_id = $house->id;
            $room->fill($post);
            $b  = $room->save();
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
     * 创建集中式房间
     */
    public function addUnion(){
        $this->load->model('storemodel');
        $room   = new Storemodel();
        DB::beginTransaction();
        $room->name='12221';
        if($room->save()){
            DB::commit();
            echo 1;
        }else{
            DB::rollBack();
            echo 2;
        }

    }

    /**
     * 分布式房间列表
     */
    public function listDot(){

    }

    /**
     * 添加分布式房间的验证规则
     */
    public function validateDotConfig(){
        $config = [
            array(
                'filed' => 'store_id',
                'label' => '门店id',
                'rules' => 'trim|required|integer'
            ),
            array(
                'filed' => 'community_id',
                'label' => '小区id',
                'rules' => 'trim|required|integer'
            ),
            array(
                'filed' => 'building',
                'label' => '楼栋号',
                'rules' => 'trim|required|integer'
            ),
            array(
                'filed' => 'unit',
                'label' => '单元号',
                'rules' => 'trim|required|integer'
            ),
            array(
                'filed' => 'room_number',
                'label' => '房间数量',
                'rules' => 'trim|required|integer'
            ),
            array(
                'filed' => 'hall_number',
                'label' => '客厅数量',
                'rules' => 'trim|required|integer'
            ),
            array(
                'filed' => 'toilet_number',
                'label' => '卫生间数量',
                'rules' => 'trim|required|integer'
            ),
            array(
                'filed' => 'contract_template_id',
                'label' => '选择合同模板',
                'rules' => 'trim|required|integer'
            ),
            array(
                'filed' => 'contract_min_time',
                'label' => '合同最少签约期限（以月份计）',
                'rules' => 'trim|required|integer'
            ),
            array(
                'filed' => 'contract_max_time',
                'label' => '合同最多签约期限（以月份计）',
                'rules' => 'trim|required|integer'
            ),
            array(
                'filed' => 'deposit_type',
                'label' => '押金信息',
                'rules' => 'trim|required|in_list[FREE]'
            ),
            array(
                'filed' => 'pay_frequency_allow',
                'label' => '允许的支付周期',
                'rules' => 'trim|required'
            ),
           /* array(
                'filed' => '',
                'label' => '',
                'rules' => 'trim|required|'
            ),*/
        ];
        return $config;
    }


}
