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

    public function __construct($id = 6)
    {
        parent::__construct();
        $this->store_id = $id;

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

        $devices->chunk(20)->each(function ($items) {
            return $this->readingRequest($items);
        });
        return true;
    }

    /**
     * 向超仪发送请求
     */
    private function readingRequest($devices)
    {
        $serialNumbers = $devices->pluck('serial_number')->toArray();

        $readings = (new Cjoymeter())->readMultipleByMeterNo($serialNumbers);

        if (!$readings) {
            log_message('error',"获取读数失败！");
        }

        return collect($readings)->map(function ($reading, $sn) use ($devices) {
            $device = $devices->where('serial_number', $sn)->first();
            $this->recordUtilityTransfer($device, $reading);
            $this->recordMeterReading($device, $reading);
        });
    }

    /**
     * 记录缓冲区的读数
     */
    private function recordUtilityTransfer($device, $reading)
    {
        $this->load->model('meterreadingtransfermodel');
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
                'confirmed'     => Meterreadingtransfermodel::UNCONFIRMED,
                'building_id'   => $device->building_id,
                'store_id'      => $this->store_id,
                'last_reading'  => $reading,
                'this_reading'  => $reading,
            ]);
        }
        return true;
    }

    /**
     * 将读数写入读数历史记录表
     */
    private function recordMeterReading($device, $reading)
    {
        $this->load->model('meterreadingmodel');
        $record = Meterreadingmodel::where([
            'type'      => $device->type,
            'room_id'   => $device->room_id,
        ])
            ->orderBy('created_at', 'DESC')
            ->first();

        if (!$record || 0.01 < $reading - $record->reading) {
            meterreadingmodel::create([
                'room_id'   => $device->room_id,
                'type'      => $device->type,
                'reading'   => $reading,
            ]);
        }
        return true;
    }
}
