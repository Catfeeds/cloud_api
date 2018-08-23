<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/libraries/Yeeuulock.php';
require_once APPPATH . '/libraries/Danbaylock.php';
require_once APPPATH . '/libraries/Cjoymeter.php';
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/3
 * Time:        10:44
 * Describe:    智能设备管理
 */

class Smartdevice extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('smartdevicemodel');
    }

    /**
     * 获取设备列表
     */
    public function listsmartdevice() {
        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');
        $post      = $this->input->post(NULL, true);
        $page      = empty($post['page']) ? 1 : intval($post['page']);
        $offset    = PAGINATE * ($page - 1);
        $where     = [];
        $store_ids = explode(',', $this->employee->store_ids);

        if (!empty($post['store_id'])) {$where['store_id'] = intval($post['store_id']);}
        if (!empty($post['device_type'])) {$where['type'] = trim($post['device_type']);}
        if (!empty($post['room_number'])) {
            $number   = trim($post['room_number']);
            $room_id  = Roomunionmodel::where('number', 'like', "$number%")->get(['id'])->toArray();
            $room_ids = [];
            if ($room_id) {
                foreach ($room_id as $key => $value) {
                    array_push($room_ids, $room_id[$key]['id']);
                }
            }

            $filed = ['id', 'room_id', 'store_id', 'type', 'supplier'];
            $count = ceil(Smartdevicemodel::where($where)
                    ->whereIn('store_id', $store_ids)
                    ->whereIn('room_id', $room_ids)
                    ->count() / PAGINATE);
            if ($page > $count || $page < 1) {
                $this->api_res(0, ['list' => []]);
                return;
            } else {
                $device = Smartdevicemodel::where($where)
                    ->whereIn('store_id', $store_ids)
                    ->whereIn('room_id', $room_ids)
                    ->with(['room' => function ($query) {
                        $query->with('store');
                        $query->orderBy('number');
                    }])
                    ->take(PAGINATE)
                    ->skip($offset)
                    ->get($filed)
                    ->toArray();
            }
        } else {
            $filed = ['id', 'room_id', 'store_id', 'type', 'supplier'];
            $count = ceil(Smartdevicemodel::where($where)->whereIn('store_id', $store_ids)->count() / PAGINATE);
            if ($page > $count || $page < 1) {
                $this->api_res(0, ['list' => []]);
                return;
            } else {
                $device = Smartdevicemodel::where($where)->whereIn('store_id', $store_ids)->with(['room' => function ($query) {
                    $query->with('store');
                }])->take(PAGINATE)->skip($offset)
                    ->orderBy('id', 'desc')->get($filed)->toArray();
            }
        }
        $this->api_res(0, ['list' => $device, 'count' => $count]);
    }

    /**
     * 获取读数（查看数据状态）
     */
    public function record() {
        $post    = $this->input->post(NULL, true);
        $page    = empty($post['page']) ? 1 : trim($post['page']);
        $id      = empty($post['id']) ? null : trim($post['id']);
        $room_id = empty($post['room_id']) ? null : trim($post['room_id']);
        $type    = empty($post['type']) ? null : trim($post['type']);
        if (!empty($post['begin_time'])) {$bt = $post['begin_time'];} else { $bt = date('Y-m-d H:i:s', 0);};
        if (!empty($post['end_time'])) {$et = $post['end_time'];} else { $et = date('Y-m-d H:i:s', time());};
        if ($type == 'LOCK') {
            $this->api_res(0, ['list' => [], 'count' => []]);
        } else {
            $this->load->model('meterreadingmodel');
            $filed     = ['type', 'reading', 'created_at'];
            $count_all = Meterreadingmodel::where('room_id', $room_id)->where('type', $type)
                ->whereBetween('updated_at', [$bt, $et])->get($filed)->count();
            $count  = ceil($count_all / PAGINATE);
            $record = Meterreadingmodel::where('room_id', $room_id)->where('type', $type)
                ->whereBetween('updated_at', [$bt, $et])
                ->orderBy('created_at', 'desc')
                ->get($filed)->map(function ($s) {
                $s->this_reading = $s->reading;
                return $s;
            })->toArray();
            for ($i = 0; $i < $count_all - 1; $i++) {
                $record[$i]['last_reading'] = $record[$i + 1]['reading'];
            }
            $record = array_chunk($record, 10);
            $this->api_res(0, ['list' => $record[$page - 1], 'count' => $count]);
        }
    }

    /**
     * 测试
     */
    public function test() {
        $deviceId = '18121960';
        $yeeuu    = new Cjoymeter($deviceId);
        $res      = $yeeuu->meterStatus();
        //var_dump($res);
        $this->api_res(0, $res);
    }

    /**
     * 获取所有CJOY所有表读数
     */
    public function getAllRecord() {
        $this->load->model('');
        $deviceId = Smartdevicemodel::where('supplier', 'CJOY')->where('id', '<', 1000)->get(['serial_number'])->toArray();
        $number   = ['17111635', '17111438', '17111672', '17111573', '17111626'];
        /*foreach ($deviceId as $key=>$value){
        array_push($number,$deviceId[$key]['serial_number']);
        };*/
        $cjoy = new Cjoymeter();
        $res  = $cjoy->readMultipleByMeterNo(['17111687']);
        var_dump($res);

        //$this->api_res(0,$res);
    }

}
