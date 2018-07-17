<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use EasyWeChat\Foundation\Application;
use Carbon\Carbon;
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/31
 * Time:        11:18
 * Describe:    优惠活动
 */
/**************************************************************/
/*         处理各种优惠活动的控制器, 目前还有许多要修改的地方         */
/**************************************************************/
date_default_timezone_set('PRC');
class Activity extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('activitymodel');
    }
    /*
     * 获取奖项
     * */
    public function activityIni()
    {
        $this->load->model('coupontypemodel');
        $this->load->model('storemodel');
        $data['coupontype'] = Coupontypemodel::where('type','CASH')->get(['id','name']);
        $data['store'] = Storemodel::get(['id','name','city']);
        if(!$data){
            $this->api_res(500);
        }else{
            $this->api_res(0,$data);
        }
    }
    /**
     * 活动列表
     */
    public function listActivity()
    {
        $post   = $this->input->post(NULL,true);
        $this->load->model('storeactivitymodel');
        $this->load->model('coupontypemodel');
        $this->load->model('drawmodel');
        $this->load->model('employeemodel');
        $store_id = isset($post['store_id'])?trim($post['store_id']):null;
        $ac_name = isset($post['activity_name'])?trim($post['activity_name']):null;
        $page   = isset($post['page'])?intval($post['page']):1;
        if((!$store_id)&&(!$ac_name)){
            $id = 'NOT';
        }elseif($store_id != null){
            $store_id = explode(',',$store_id);
            $activity_id1 = Storeactivitymodel::whereIn('store_id',$store_id)->get(['activity_id'])->toArray();
            $id_1=[];
            foreach ($activity_id1 as $key=>$value){
                $id_1[] = $value['activity_id'];
            }
            $activity_id2 = Activitymodel::where('name','like','%'.$ac_name.'%')
                ->whereIn('id',$id_1)->where('activity_type', '!=', '0')->get(['id'])->toArray();
            $id_2=[];
            foreach ($activity_id2 as $id){
                $id_2[] = $id['id'];
            }
            $id = $id_2;
        }else{
            $activity_id2 = Activitymodel::where('name','like','%'.$ac_name.'%')->where('activity_type','!=','0')
                ->get(['id'])->toArray();
            $id_2=[];
            foreach ($activity_id2 as $id){
                $id_2[] = $id['id'];
            }
            $id = $id_2;
        }
        $offset = ($page-1)*PAGINATE;
        $filed = ['id','name','start_time','end_time','description','coupon_info','limit','current_id','qrcode_url','activity_type'
        ,'one_prize','two_prize','three_prize'];
        if($id == 'NOT') {
            $activity = Activitymodel::where('activity_type', '!=', '0')->take(PAGINATE)->skip($offset)
                ->orderBy('end_time', 'desc')
                ->get($filed)->ToArray();
        }else{
            $activity = Activitymodel::where('activity_type', '!=', '0')->take(PAGINATE)->skip($offset)->whereIn('id', $id)
                ->orderBy('end_time', 'desc')
                ->get($filed)->ToArray();
        }
        if(!$activity){
            $this->api_res(1007);
        }
        $data = array();
        foreach($activity as $key=>$coupon){
            $p = [$coupon['one_prize'],$coupon['two_prize'],$coupon['three_prize']];
            $couponarr= Coupontypemodel::whereIn('id',$p)->get(['name'])->toArray();
            $str = '';
            foreach ($couponarr as $value){
                $str.=$value['name'].'/';
            }
            $participate = Drawmodel::where('activity_id',$coupon['id'])->orderby('costomer_id')->count();
            $Lottery_number = Drawmodel::where('activity_id',$coupon['id'])->count();
            $lucky_draw = Drawmodel::where(['activity_id'=>$coupon['id'],'is_draw'=>'1'])->count();
            $employee_name = Employeemodel::where('id',$coupon['current_id'])->first(['name']);
            $data[$key]['id']=$coupon['id'];
            $data[$key]['user'] = $employee_name->name;
            $data[$key]['name']=$coupon['coupon_info'];
            $data[$key]['start_time']=date("Y-m-d H:i", strtotime($coupon['start_time']));
            $data[$key]['end_time']=date("Y-m-d H:i", strtotime($coupon['end_time']));
            $data[$key]['prize'] = $str;
            $limit= unserialize($coupon['limit']);
            $data[$key]['customer'] = $limit['com'];
            $data[$key]['limit'] = $limit['limit'];
            $data[$key]['participate'] = $participate;
            $data[$key]['Lottery_number'] = $Lottery_number;
            $data[$key]['lucky_draw'] = $lucky_draw;
            $data[$key]['url'] = $coupon['qrcode_url'];
            if($coupon['activity_type'] == '-1'){
                $data[$key]['status'] = 'Lowerframe';
            }elseif(time()<strtotime($coupon['start_time'])){
                $data[$key]['status'] = 'Notbeginning';
            }elseif(time()>strtotime($coupon['end_time'])){
                $data[$key]['status'] = 'End';
            }elseif(time()<strtotime($coupon['end_time']) && time()>strtotime($coupon['start_time'])){
                $data[$key]['status'] = 'Normal';
            }
        }
        $this->api_res(0,['count'=>$page,'list'=>$data]);
    }
    /*
     * 新增活动
     * 大转盘的活动
     * */
        public function addTrntable(){
        $post = $this->input->post(null,true);
        $this->load->model('storeactivitymodel');
        $config = $this->validation();
        array_pull($config, '0');
        if(!$this->validationText($config)){
            $fieldarr = [ 'name', 'start_time', 'end_time','slogan','description','limit','one_prize',
            'two_prize','three_prize'];
            $this->api_res(1002,['error'=>$this->form_first_error($fieldarr)]);
            return false;
        }
        $arr = [$post['one_prize'],$post['two_prize'],$post['three_prize']];

        if (count($arr) != count(array_unique($arr))) {
            $this->api_res(11101);
            return false;
        }
        $limit = ['com' => $post['customer'],
                   'limit' => $post['limit']];
        $activity['coupon_info'] = $post['name'];
        $activity['start_time'] = $post['start_time'];
        $activity['end_time'] = $post['end_time'];
        $activity['one_prize'] = $post['one_prize'];
        $activity['two_prize'] = $post['two_prize'];
        $activity['three_prize'] = $post['three_prize'];
        $activity['one_count'] = $post['one_count'];
        $activity['name'] = $post['slogan'];
        $activity['two_count'] = $post['two_count'];
        $activity['three_count'] = $post['three_count'];
        $activity['description'] = $post['description'];
        $activity['limit'] = serialize($limit);
        $activity['current_id'] = CURRENT_ID;
        $activity['share_img'] = $this->splitAliossUrl($post['images']);/*$this->splitAliossUrl($post['images'],true)*/;
        $activity['share_des'] = $post['share_des'];
        $activity['share_title'] = $post['share_title'];
        $activity['activity_type'] = 1;//tweb.funxdata.com/#/turntable
        $insertId = Activitymodel::insertGetId($activity);
          $store_id =explode(',', $post['store_id']);
        $ac = Activitymodel::find($insertId);
        $ac->qrcode_url ="tweb.funxdata.com/#/turntable?id=".$insertId."";
        $ac->save();
        foreach ($store_id as $value){
            $data = ['store_id'=>$value, 'activity_id' => $insertId];
            $store= Storeactivitymodel::insert($data);
        }
        if ($insertId && $store) {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }
    }
    /*
     * 刮刮乐活动addScratch
     * */
    public function addScratch(){
        $post = $this->input->post(null,true);
        $this->load->model('storeactivitymodel');
        $config = $this->validation();
        array_pull($config, '0');
        array_pull($config, '4');
        if(!$this->validationText($config)){
            $fieldarr = [ 'name', 'start_time', 'end_time','description','limit','one_prize',
                'two_prize','three_prize'];
            $this->api_res(1002,['error'=>$this->form_first_error($fieldarr)]);
            return false;
        }
        $arr = [$post['one_prize'],$post['two_prize'],$post['three_prize']];
        if (count($arr) != count(array_unique($arr))) {
            $this->api_res(11101);
            return false;
        }
        $limit = ['com' => $post['customer'],
            'limit' => $post['limit']];
        $activity['coupon_info'] = $post['name'];
        $activity['start_time'] = $post['start_time'];
        $activity['end_time'] = $post['end_time'];
        $activity['one_prize'] = $post['one_prize'];
        $activity['two_prize'] = $post['two_prize'];
        $activity['three_prize'] = $post['three_prize'];
        $activity['one_count'] = $post['one_count'];
        $activity['two_count'] = $post['two_count'];
        $activity['three_count'] = $post['three_count'];
        $activity['description'] = $post['description'];
        $activity['limit'] = serialize($limit);
        $activity['current_id'] = CURRENT_ID;
        $activity['share_img'] = $this->splitAliossUrl($post['images']);/*$this->splitAliossUrl($post['images'],true)*/;
        $activity['share_des'] = $post['share_des'];
        $activity['share_title'] = $post['share_title'];
        $activity['activity_type'] = 2;//tweb.funxdata.com/#/turntable
        $insertId = Activitymodel::insertGetId($activity);
        $store_id =explode(',', $post['store_id']);
        $ac = Activitymodel::find($insertId);
        $ac->qrcode_url ="tweb.funxdata.com/#/turntable?id=".$insertId."";
        $ac->save();
        foreach ($store_id as $value){
            $data = ['store_id'=>$value, 'activity_id' => $insertId];
            $store= Storeactivitymodel::insert($data);
        }
        if ($insertId && $store) {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }
    }

    /*
     * 下架活动
     * */
    public function LowerActivity(){
        $post = $this->input->post(null,true);
        $activity_id = isset($post['id'])?$post['id']:null;
        if(!$activity_id){
            $this->api_res(1002);
            return false;
        }
        $activity = Activitymodel::find($activity_id);
        $activity->activity_type = -1;
        if($activity->save()){
            $data=['status'=> 'Lowerframe'];
            $this->api_res(0,$data);
        }else{
            $this->api_res(500);
        }
    }

   /*
    * 生成活动二维码
    * */
    public function activityCode(){
        $post = $this->input->post(null,true);
        $id = isset($post['id'])?$post['id']:null;
        if (!$id) {
            $this->api_res(1002);
            return false;
        }
        $this->load->helper('common');
        $activity   = Activitymodel::find($id);
        if(!$activity){
            $this->api_res(1007);
            return;
        }
        if($activity->activity_type== -1){
            $this->api_res(11102);
            return;
        }
        try{
            $app        = new Application(getWechatCustomerConfig());
            $qrcode     = $app->qrcode;
            $result     = $qrcode->temporary($id, 6 * 24 * 3600);
            $ticket     = $result->ticket;
            $url        = $qrcode->url($ticket);
            $this->api_res(0,['url'=>$url]);
        }catch (Exception $e){
            log_message('error',$e->getMessage());
            throw $e;
        }
    }

    public function validation()
    {
        $this->load->library('form_validation');
        $config = array(
            array(
                'field' => 'type',
                'label' => '活动类型',
                'rules' => 'trim|required|in_list[ATTRACT,NORMAL,DISCOUNT]',
            ),
            array(
                'field' => 'name',
                'label' => '活动名称',
                'rules' => 'trim|required|max_length[255]',
            ),
            array(
                'field' => 'start_time',
                'label' => '开始时间',
                'rules' => 'trim|required|max_length[255]',
            ),
            array(
                'field' => 'end_time',
                'label' => '结束时间',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'slogan',
                'label' => '口号',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'description',
                'label' => '描述',
                'rules' => 'trim|required|max_length[255]',
            ),
            array(
                'field' => 'limit',
                'label' => '抽奖限制',
                'rules' => 'trim|required',
            ),
            array(

                'field' => 'store_id',
                'label' => '门店',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'one_prize',
                'label' => '奖品1',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'two_prize',
                'label' => '奖品2',
                'rules' => 'trim|required',

            ),
            array(
                'field' => 'three_prize',
                'label' => '奖品3',
                'rules' => 'trim|required',
            ),
        );
        return $config;
    }
}
