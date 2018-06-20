<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/libraries/Yeeuulock.php';
require_once APPPATH.'/libraries/Danbaylock.php';
require_once APPPATH.'/libraries/Cjoymeter.php';
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
    public function listsmartdevice()
    {
        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');
        $post           = $this->input->post(NULL,true);
        $page           = empty($post['page'])?1:intval($post['page']);
        $offset         = PAGINATE*($page-1);

        $where          = [];
        $condition      = [];
        if(!empty($post['city'])){$where['city']    = $post['city'];}
        if(!empty($post['store_id'])){$where['id']  = $post['store_id'];}

        if(!empty($post['room_number'])) {
            $room_number = trim($post['room_number']);
            $room_id = Roomunionmodel::where('number',$room_number)->get(['id'])->toArray();
            if($room_id){
                $condition['room_id']      = $room_id;
            }else{
                $this->api_res(0,['list'=>[]]);
                return ;
            }
        }
        if ($where){
            $store_id = Storemodel::where($where)->get(['id'])->toArray();
            $condition['store_id'] = $store_id;
        }

        if (!empty($post['device_type'])){$condition['type'] = $post['device_type'];}
        $filed = ['id','room_id','store_id','type','supplier'];
        if($condition){
            $count          = ceil(Smartdevicemodel::where($condition)->count()/PAGINATE);
            if ($page>$count||$page<1){
                $this->api_res(0,['list'=>[]]);
                return ;
            }else{
                $device = Smartdevicemodel::where($condition)->with(['room'=>function($query){
                    $query->with('store');
                }])->with('store')
                                            ->take(PAGINATE)->skip($offset)
                                            ->orderBy('id','desc')->get($filed)->toArray();
            }

        }else{
            $count = ceil(Smartdevicemodel::count()/PAGINATE);
            if ($page>$count||$page<1){
                $this->api_res(0,['list'=>[]]);
                return ;
            }else {
                $device = Smartdevicemodel::with(['room'=>function($query){
                    $query->with('store');
                }])->with('store')
                                            ->take(PAGINATE)->skip($offset)
                                            ->orderBy('id', 'desc')->get($filed)->toArray();
            }
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

    /**
     * 测试
     */
    public function test()
    {
        $deviceId = '18121960';
        $yeeuu = new Cjoymeter($deviceId);
        $res = $yeeuu->meterStatus();
        //var_dump($res);
        $this->api_res(0,$res);
    }

    /**
     * 获取所有CJOY所有表读数
     */
    public function getAllRecord()
    {
        $this->load->model('');
        $deviceId = Smartdevicemodel::where('supplier','CJOY')->where('id','<',1000)->get(['serial_number'])->toArray();
        $number = ['17111635','17111438','17111672','17111573','17111626'];
        /*foreach ($deviceId as $key=>$value){
            array_push($number,$deviceId[$key]['serial_number']);
        };*/
        $cjoy = new Cjoymeter();
        $res = $cjoy->readMultipleByMeterNo(['17111687']);
        var_dump($res);


        //$this->api_res(0,$res);
    }

}
