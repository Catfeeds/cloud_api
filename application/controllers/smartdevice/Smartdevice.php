<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/3
 * Time:        10:44
 * Describe:    智能设备管理
 */

class Smartdevice extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('smartdevicemodel');
    }

    /**
     * 获取设备列表
     */
    public function index()
    {
        $this->load->model('storemodel');
        $this->load->model('roommodel');
        $post           = $this->input->post(NULL,true);
        $page           = empty($post['page'])?1:trim($post['page']);
        $offset         = PAGINATE*($page-1);
        $count          = ceil(Smartdevicemodel::count()/PAGINATE);
        $room_number    = empty($post['room_number'])?NULL:trim($post['room_number']);
        $where          = [];
        $condition      = [];
        if(!empty($post['city'])){$where['city']    =$post['city'];}
        if(!empty($post['store_id'])){$where['id']  =$post['store_id'];}
        if ($where){$condition['store_id']          = Storemodel::where($where)->get(['id']);}
        if($room_number){$condition['room_id']      = Roommodel::where('number',$room_number)->get(['id']);}
        if (!empty($post['device_type'])){$condition['type'] = $post['device_type'];}
        $filed = ['id','room_id','type','supplier'];
        if($condition){
            $device = Smartdevicemodel::where($condition)
                                        ->take(PAGINATE)->skip($offset)
                                        ->orderBy('id','desc')->get($filed)->toArray();
        }else{
            $device = Smartdevicemodel::take(PAGINATE)->skip($offset)
                                        ->orderBy('id','desc')->get($filed)->toArray();
        }
        $this->api_res(0,['list'=>$device,'count'=>$count]);
    }

    /**
     * 获取读数（查看数据状态）
     */
    public function record()
    {
        $post   = $this->input->post(NULL,true);
        $page           = empty($post['page'])?1:trim($post['page']);
        $offset         = PAGINATE*($page-1);

        $id     = empty($post['id'])?null:trim($post['id']);
        $type   = empty($post['type'])?null:trim($post['type']);
        if(!empty($post['begin_time'])){$bt=$post['begin_time'];}else{$bt = date('Y-m-d H:i:s',0);};
        if(!empty($post['end_time'])){$et=$post['end_time'];}else{$et = date('Y-m-d H:i:s',time());};
        if($type == 'LOCK'){
            $this->load->model('smartlockrecordmodel');
            $count  = ceil(Smartlockrecordmodel::count()/PAGINATE);
            $filed  = ['smart_device_type','unlock_person','unlock_way','updated_at'];
            $record = Smartlockrecordmodel::where('smart_device_id',$id)
                                            ->whereBetween('updated_at',[$bt,$et])
                                            ->take(PAGINATE)->skip($offset)
                                            ->orderBy('id','desc')->get($filed)->toArray();
        }else{
            $this->load->model('smartdevicerecordmodel');
            $count  = ceil(Smartdevicerecordmodel::count()/PAGINATE);
            $filed  = ['smart_device_type','last_reading','this_reading','updated_at'];
            $record = Smartdevicerecordmodel::where('smart_device_id',$id)
                                            ->whereBetween('updated_at',[$bt,$et])
                                            ->take(PAGINATE)->skip($offset)
                                            ->orderBy('id','desc')->get($filed)->toArray();
        }
        $this->api_res(0,['list'=>$record,'count'=>$count]);
    }
}
