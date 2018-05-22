<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Illuminate\Database\Capsule\Manager as DB;
use EasyWeChat\Foundation\Application;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/22 0022
 * Time:        9:23
 * Describe:    入住
 */
class Resident extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 检查集中式房间号是否存在
     * 返回room_id
     */
    public function checkRoomUnion()
    {

        $store_id   = $this->employee->store_id;
        $room_number    = $this->input->post('room_number',true);
        $where      = ['store_id'=>$store_id,'room_number'=>$room_number];
        $this->load->model('roomunionmodel');
        if(!$room=Roomunionmodel::where($where)->first()){
            $this->api_res(1007);
            return;
        }
        $room_id    = $room->id;
        if(!$room->isBlank()){
            $this->api_res(10010);
            return;
        }
        $this->api_res(0,[
            'room_id'=>$room_id,
            'rent_price'=>$room->rent_price,
            'property_price'=>$room->property_price,
        ]);
    }


    /**
     * 办理入住
     * @param $store_id
     * @param $room_number
     */
    public function checkIn()
    {
        $field  = [
            'room_id','begin_time','people_count','contract_time','discount_id','first_pay_money',
            'deposit_money','deposit_month','tmp_deposit',
        ];
        $post   = $this->input->post(null,true);
        //验证提交的信息
        if(!$this->validationText($this->validateCheckIn())){
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $this->load->model('roomunionmodel');
        //判断房间是否空闲
        $room   = Roomunionmodel::find($post['room_id']);
        if(!$room->isBlank()){
            $this->api_res(10010);
            return;
        }
        //
        $this->load->model('residentmodel');
        $resident   = new Residentmodel();
        try{
            DB::beginTransaction();
            $room->status   = Roomunionmodel::OCCUPIED;
            $a = $room->save();
            $resident->fill($post);
            $resident->real_rent_money  = $room->rent_price;
            $resident->real_property_costs  = $room->property_price;
            //$resident->employee_id  = $this->employee->id;
            $b  = $resident->save();
            if($a&&$b){
                DB::commit();
                $this->api_res(0,['resident_id'=>$resident->id]);
            }else{
                DB::rollBack();
                $this->api_res(1009);
            }
        }catch (Exception $e){
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 办理入住1表单验证
     */
    public function validateCheckIn()
    {
        return array(
            array(
                'field' => 'room_id',
                'label' => '房间号',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'begin_time',
                'label' => '开始时间',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'people_count',
                'label' => '入住人数',
                'rules' => 'required|trim|integer',
            ),
            array(
                'field' => 'contract_time',
                'label' => '合同时长',
                'rules' => 'required|trim|integer',
            ),
            array(
                'field' => 'discount_id',
                'label' => '折扣id',
                'rules' => 'trim|integer',
            ),
//            array(
//                'field' => 'rent_type',
//                'label' => '出租类型',
//                'rules' => 'trim|required|in_list(LONG,SHORT)',
//            ),
//            array(
//                'field' => 'real_rent_money',
//                'label' => '实际租金',
//                'rules' => 'trim|required|integer',
//            ),
//            array(
//                'field' => 'real_property_costs',
//                'label' => '实际物业费',
//                'rules' => 'trim|integer|required',
//            ),
            array(
                'field' => 'first_pay_money',
                'label' => '首次支付',
                'rules' => 'trim|numeric|required',
            ),
            array(
                'field' => 'deposit_money',
                'label' => '押金',
                'rules' => 'trim|numeric|required',
            ),
            array(
                'field' => 'deposit_month',
                'label' => '押金月份',
                'rules' => 'trim|integer|required',
            ),
            array(
                'field' => 'tmp_deposit',
                'label' => '其他押金',
                'rules' => 'trim|numeric|required',
            ),
            array(
                'field' => 'remark',
                'label' => '备注',
                'rules' => 'trim',
            ),
        );
    }

    /**
     * 办理入住2 住户信息
     */
    public function checkInData(){
        $field=[
            'resident_id','name','phone','card_type','card_number','card_one','card_two','card_three',
            'name_two','phone_two','card_type_two','card_number_two','alter_phone','alternative','address'
        ];
        if(!$this->validationText($this->validateCheckIn())){
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $post   = $this->input->post(null,true);
        if(!$this->checkPhoneNumber($post['phone'])){
            $this->api_res(1002,['error'=>'请检查手机号']);
            return;
        }
        if(!$this->checkIdCardNumber($post['card_type'],$post['card_number'])){
            $this->api_res(1002,['error'=>'请检查身份证号']);
            return;
        }
        if(isset($post['name_two'])){
            if(empty($post['phone_two']) || empty($post['card_type_two'] || empty($post['card_number_two']))){
                $this->api_res(1002,['error'=>'住户二信息不全']);
                return;

            }
            if(!$this->checkPhoneNumber($post['phone_two'])){
                $this->api_res(1002,['error'=>'请检查手机号']);
                return;
            }
            if(!$this->checkIdCardNumber($post['card_type_two'],$post['card_number_two'])){
                $this->api_res(1002,['error'=>'请检查身份证号']);
                return;
            }
        }
        $this->load->model('residentmodel');
        $resident   = Residentmodel::find($post['resident_id']);
        $resident->fill($post);
        $resident->employee_id  = $this->employee->id;
        $resident->status  = Residentmodel::STATE_NOTPAY;
        $resident->card_one = $this->splitAliossUrl($post['card_one']);
        $resident->card_two = $this->splitAliossUrl($post['card_two']);
        $resident->card_three = $this->splitAliossUrl($post['card_three']);
        if($resident->save()){
            $this->api_res(0,['resident_id'=>$resident->id]);
        }else{
            $this->api_res(1009);
        }
    }

    /**
     * 办理入住2 住户1信息验证
     */
    public function validateCheckInData(){
        return array(
            array(
                'field' => 'resident_id',
                'label' => '住户id',
                'rules' => 'required|trim|integer',
            ),
            array(
                'field' => 'name',
                'label' => '住户名称',
                'rules' => 'required|trim|integer',
            ),
            array(
                'field' => 'phone',
                'label' => '手机号',
                'rules' => 'required|trim|integer',
            ),
            array(
                'field' => 'card_type',
                'label' => '证件类型',
                'rules' => 'required|trim|in_list(0,1,2,6,A,B,C,E,F,P,BL)',
            ),
            array(
                'field' => 'card_number',
                'label' => '证件号码',
                'rules' => 'required|trim|integer',
            ),
            array(
                'field' => 'alternative',
                'label' => '紧急联系人',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'alter_phone',
                'label' => '联系方式',
                'rules' => 'required|trim|integer',
            ),
            array(
                'field' => 'address',
                'label' => '通讯地址',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'card_one',
                'label' => '证件照1',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'card_two',
                'label' => '证件照2',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'card_three',
                'label' => '证件照3',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'name_two',
                'label' => '住户名称',
                'rules' => 'trim|integer',
            ),
            array(
                'field' => 'phone_two',
                'label' => '手机号',
                'rules' => 'trim|integer',
            ),
            array(
                'field' => 'card_type_two',
                'label' => '证件类型',
                'rules' => 'trim|in_list(0,1,2,6,A,B,C,E,F,P,BL)',
            ),
            array(
                'field' => 'card_number_two',
                'label' => '证件号码',
                'rules' => 'trim|integer',
            ),

        );
    }

    /**
     * 取消办理入住
     */
    public function destory(){
        $resident_id    = $this->input->post('resident_id',true);
        if(empty($resident_id)){
            $this->api_res(1005);
            return;
        }

        $this->load->model('residentmodel');
        $resident   = Residentmodel::find($resident_id);
        if($resident->status!=Residentmodel::STATE_NOTPAY){
            $this->api_res(10011);
            return;
        }
        $this->load->model('roomunionmodel');
        $room = $resident->roomunion();
        if($room->store_id!=$this->employee->store_id){
            $this->api_res(10012);
            return;
        }
        if(!$room->status !== Roomunionmodel::OCCUPIED){
            $this->api_res(10013);
            return;
        }
        try{
            DB::beginTransaction();
            $room->status=Roomunionmodel::BLANK;
            $a  = $room->save();
            $b=$resident->delete();
            if($a && $b){
                DB::commit();
                $this->api_res(0);
            }else{
                DB::rollBack();
                $this->api_res(1009);
            }
        }catch (Exception $e){
            DB::rollBack();
            throw $e;
        }
    }

    /**
     *生成住户二维码
     */
    public function showQrCode(){
        $resident_id = $this->input->post('resident_id',true);
        $this->load->helper('common');
        $this->load->model('residentmodel');
        $resident   = Residentmodel::find($resident_id);
        if(!$resident){
            $this->api_res(1007);
            return;
        }
        if($resident->status!==Residentmodel::STATE_NOTPAY){
            $this->api_res(10011);
            return;
        }
        $app        = new Application(getWechatCustomerConfig());
        $qrcode     = $app->qrcode;
        $result     = $qrcode->temporary(QRCODERESIDENT.$resident_id, 6 * 24 * 3600);
        $ticket     = $result->ticket;
        $url        = $qrcode->url($ticket);
        $this->api_res(0,['url'=>$url]);
    }


    /**
     * 检查手机号码的有效性
     */
    public function checkPhoneNumber($phone)
    {
        $this->load->helper('check');
        if (!isMobile($phone)) {
            log_message('debug','请检查手机号码');
            return false;
        }
        return true;
    }

    /**
     * 检查证件号码的有效性
     */
    public function checkIdCardNumber($type, $cardNumber)
    {
        $this->load->helper('check');
        if (Residentmodel::CARD_ZERO == $type AND !isIdNumber($cardNumber)) {
            log_message('debug','请检查证件号码的有效性');
            return false;
        }

        return true;
    }


}
