<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use GuzzleHttp\Client;
require_once APPPATH.'/libraries/Cjoymeter.php';
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/6/6
 * Time:        22:12
 * Describe:    读CJOY的表
 */
class Readmeter extends MY_Controller
{
    protected $store_id;

    public $tries = 3;

    public function __construct($id = 8)
    {
        parent::__construct();
        $this->store_id = $id;
        $this->load->model('meterreadingtransfermodel');
    }

    public function handle()
    {
        $this->load->model('smartdevicemodel');
        $devices = smartdevicemodel::join('boss_room_union', function ($join) {
            $join->on('boss_smart_device.room_id', '=', 'boss_room_union.id');
        })
            ->where('boss_smart_device.supplier', 'CJOY')
            ->where('boss_room_union.store_id', $this->store_id)
            ->select([
                'boss_smart_device.room_id',
                'boss_smart_device.type',
                'boss_smart_device.serial_number',
                'boss_room_union.building_id',
            ])
            ->get();
        if (0 == count($devices)) {
            log_message('error','无表计信息！');
        }

        $serialNumbers = collect($devices)->pluck('serial_number')->toArray();

        $readings = (new Cjoymeter())->readMultipleByMeterNo($serialNumbers);
var_dump($readings);die();
        if (!$readings) {
            log_message('error',"获取读数失败！");
        }
//var_dump($readings);
        collect($readings)->map(function ($reading, $sn) use ($devices) {
            $device = $devices->where('serial_number', $sn)->first();
            $this->recordMeterReading($device, $reading);
        });
        return true;
    }

    /**
     * 记录读数
     */
    private function recordMeterReading($device, $reading)
    {
        $transfer   = Meterreadingtransfermodel::where([
            'room_id' => $device->room_id,
            'type'    => $device->type,
        ])->first();

        if ($transfer) {
            $transfer->this_reading = $reading;
            $transfer->confirmed    = Meterreadingtransfermodel::UNCONFIRMED;
            $transfer->save();
        } else {
            Meterreadingtransfermodel::updateOrCreate([
                'room_id' => $device->room_id,
                'type'    => $device->type,
            ],[
                'confirmed'    => Meterreadingtransfermodel::UNCONFIRMED,
                'building_id'  => $device->building_id,
                'store_id' => $this->store_id,
                'last_reading' => $reading,
                'this_reading' => $reading,
            ]);
        }
        return true;
    }
}
