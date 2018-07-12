<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use EasyWeChat\Foundation\Application;
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
    public function ActivityIni()
    {
        $this->load->model('coupontypemodel');
        $this->load->model('storemodel');
        $data['coupontype'] = Coupontypemodel::where('type','CASH')->get(['id','name']);
        $data['stoer'] = Storemodel::get(['id','name','city']);
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
            $id = null;
        }elseif($store_id){
            $store_id = explode(',',$store_id);
            $activity_id1 = Storeactivitymodel::whereIn('store_id',$store_id)->get(['activity_id'])->toArray();

            foreach ($activity_id1 as $key=>$value){
                $id_1[] = $value['activity_id'];
            }

            $activity_id2 = Activitymodel::where('name','like','%'.$ac_name.'%')->where('activity_type', '!=', '0')
                ->whereIn('id',$id_1)->get(['id'])->toArray();
            foreach ($activity_id2 as $id){
                $id_2[] = $id['id'];
            }
            $id = $id_2;
        }else{
            $activity_id2 = Activitymodel::where('name','like','%'.$ac_name.'%')->where('activity_type','!=','0')->get(['id'])->toArray();
            foreach ($activity_id2 as $id){
                $id_2[] = $id['id'];
            }
            $id = $id_2;
        }
        $offset = ($page-1)*PAGINATE;
        $filed = ['id','name','start_time','end_time','description','coupon_info','limit','current_id','qrcode_url','activity_type'];
        if($id == null) {
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
            $cou = unserialize($coupon['coupon_info']);
            $p = explode(',',$cou['prize']);
            $c = explode(',',$cou['count']);
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
            $data[$key]['name']=$coupon['name'];
            $data[$key]['start_time']=$coupon['start_time'];
            $data[$key]['end_time']=$coupon['end_time'];
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
              $fieldarr = [ 'name', 'start_time', 'end_time','coupon_info','description','limit'];
              $this->api_res(1002,['error'=>$this->form_first_error($fieldarr)]);
              return false;
          }
        $coupon_info = ['prize'=> $post['prize'],
                         'count'=> $post['count']];
        $limit = ['com' => $post['customer'],
                   'limit' => $post['limit']];
        $activity['name'] = $post['name'];
        $activity['start_time'] = $post['start_time'];
        $activity['end_time'] = $post['end_time'];
        $activity['coupon_info'] = serialize($coupon_info);
        $activity['limit'] = serialize($limit);
        $activity['current_id'] = CURRENT_ID;
        $activity['activity_type'] = 1;//tweb.funxdata.com/#/turntable
        $insertId = Activitymodel::getInsertId($activity);

          $store_id =explode('', $post['store_id']);
        $ac = new Activitymodel();
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
     * 刮刮乐活动
     * */
    public function addScratch(){
        $post = $this->input->post(null,true);
        $this->load->model('storeactivitymodel');
        $config = $this->validation();
        array_pull($config, '0');
        if(!$this->validationText($config)){
            $fieldarr = [ 'name', 'start_time', 'end_time','coupon_info','description','limit'];
            $this->api_res(1002,['error'=>$this->form_first_error($fieldarr)]);
            return false;
        }
        $coupon_info = ['prize'=> $post['prize'],
            'count'=> $post['count']];
        $limit = ['com' => $post['customer'],
            'limit' => $post['limit']];
        $activity['name'] = $post['name'];
        $activity['start_time'] = $post['start_time'];
        $activity['end_time'] = $post['end_time'];
        $activity['coupon_info'] = serialize($coupon_info);
        $activity['limit'] = serialize($limit);
        $activity['current_id'] = CURRENT_ID;
        $activity['activity_type'] = 2;
        $insertId = Activitymodel::getInsertId($activity);
        $ac = new Activitymodel();
        $ac->qrcode_url ="tweb.funxdata.com/#/scraping?id=".$insertId."";
        $ac->save();
        $store_id =explode(',', $post['store_id']);
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
        if($activity_id == null){
            $this->api_res(500);
            return false;
        }
        $activity = Activitymodel::where('id',$activity_id);
        $activity->activity_type = -1;

        if($activity->save()){
            $data=['status'=> 'Lowerframe'];
            $this->api_res(0,$data);
        }else{
            $this->api_res(500);
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
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'end_time',
                'label' => '结束时间',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'coupon_info',
                'label' => '奖项',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'description',
                'label' => '开始时间',
                'rules' => 'trim|required|max_length[255]',
            ),
            array(
                'field' => 'limit',
                'label' => '抽奖限制',
                'rules' => 'trim|required',
            ),
        );
        return $config;
    }
}
