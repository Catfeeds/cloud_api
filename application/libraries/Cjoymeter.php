<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use GuzzleHttp\Client;
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/11
 * Time:        2:08
 * Describe:    智能设备-超仪电表
 */
class Cjoymeter
{
    private $clientId;
    private $publicKeyPath;
    private $baseUrl;
    private $deviceNumber;

    public function __construct($deviceNumber = null)
    {
        $this->deviceNumber     = $deviceNumber;
        $this->baseUrl          = config_item('joyMeterApiUrl');
        $this->clientId         = config_item('joyMeterClientId');
        $this->publicKeyPath    = config_item('joyPublicKeyPath');
    }

    /**
     * 生成access_token
     * @return null|string
     */
    public function getAccessToken()
    {
        $key = <<<EOF
-----BEGIN PUBLIC KEY-----
MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAJQeFrVhmHoWYNwPkXFVScpdwsZ/BnVh
sUuGGvozfgcyde6Q7nFaTmvNBGuxbSqsSmatQLKEZWkPDDzP/Yv7zPcCAwEAAQ==
-----END PUBLIC KEY-----
EOF;
        //不要动界定符中内容，空格换行也不行（逼死强逼症）
        $publicKey = openssl_pkey_get_public($key);
        $data   = json_encode([
            'client_id' => $this->clientId,
            'datetime'  => date('YmdHis'),
        ]);
        return openssl_public_encrypt($data ,$encrypted, $publicKey) ? base64_encode($encrypted) : null;
    }

    /**
     * 向超仪服务器发送请求
     */
    private function request($uri, array $data)
    {
        $data['access_token']   = $this->getAccessToken();
        $res = (new Client())->request('POST', $this->baseUrl . $uri, [
            'form_params' => $data,
        ])->getBody()->getContents();
        //var_dump($res);
        $res = json_decode($res, true);
        return $res;
        //return $res['status'] == 1 ? $res['data'] : null;
    }

    /**
     * 查询电表状态（电表的连网状态和通电状态）
     */
    public function meterStatus()
    {
        return $this->request('queryMeterStatus.do', [
            'meterNo'   => $this->deviceNumber,
        ]);
    }

    /**
     * 查询设备几天的耗电量
     * 日期格式为date('Y-m-d')
     */
    public function powerCostPerDay($startDate, $endDate)
    {
        return $this->request('queryPowerCostPerDay.do', [
            'meterNo'       => $this->deviceNumber,
            'start_time'    => $startDate,
            'end_time'      => $endDate,
        ]);
    }

    /**
     * 查询设备在一段时间内的充值信息(充值电量)
     * 日期格式为date('Y-m-d')
     */
    public function rechargeInfo($startDate, $endDate)
    {
        return $this->request('queryRechargeInfo.do', [
            'meterNo'    => $this->deviceNumber,
            'start_time' => $startDate,
            'end_time'   => $endDate,
        ]);
    }

    /**
     * 根据表号查询用户信息
     */
    public function userInfoByMeterNo()
    {
        return $this->request('findUserByMeterNo.do', [
            'meterNo'   => $this->deviceNumber,
        ]);
    }

    /**
     * 根据表号充值(量)
     */
    public function rechargeByMeterNo( $money)
    {
        return $this->request('rechargeByMeterNo.do', [
            'meterNo'   => $this->deviceNumber,
            'money'     => $money,
        ]);
    }

    /**
     * 根据表号退费
     */
    public function refundByMeterNo( $money)
    {
        return $this->request('refundByMeterNo.do', [
            'meterNo' => $this->deviceNumber,
            'money'   => $money,
        ]);
    }

    /**
     * 根据表号清空余额
     */
    public function clearBalanceByMeterNo()
    {
        return $this->request('clearBalanceByMeterNo.do', [
            'meterNo'  => $this->deviceNumber,
        ]);
    }

    /**
     * 根据表号控制继电器
     * action表示动作目的, 1:打开, 0:关闭
     */
    public function operateTheMeter( $action = 0)
    {
        return $this->request('mbusControlByMeterNo.do', [
            'meterNo'   => $this->deviceNumber,
            'action'    => $action,
        ]);
    }


    /**
     * 根据表号发送短信通知
     */
    public function sendMessageToUser()
    {
        return $this->request('sendSmsByMeterNo.do', [
            'meterNo'   => $this->deviceNumber,
        ]);
    }


    /**
     * 根据表号抄表（多表）
     */
    public function readMultipleByMeterNo(array $number)
    {
        $res = $this->request('readByMeterNo.do', [
            'meterNo'   => implode($number, ','),
        ]);
        $res = $res['data'];
        //$result = [];
        //$result['meter_no'] = $res['data']['meter_no'];
        //$result['this_read']= $res['data']['this_read'];
        //var_dump($res);
        return collect($res)->pluck('this_read','meter_no')->toArray();
    }

    /**
     * 根据表号抄表(单表)
     */
    public function readByMeterNumber()
    {
        return $this->request('readByMeterNo.do', [
            'meterNo'   => $this->deviceNumber,
        ]);
    }


    /**
     * 根据表号入住,退住接口
     * action: in->入住, out->退住
     * time: date('Y-m-d H:i:s')
     */
    public function checkInOut( $action, $peopleCount = 1, $time)
    {
        return $this->request('checkInOut.do', [
            'meterNo'   => $this->deviceNumber,
            'json'      => json_encode([
                'meterNo'   => $this->deviceNumber,
                'action'    => $action,
                'peoples'   => $peopleCount,
                'datatime'  => $time,
            ]),
        ]);
    }


    /**
     * 根据日期查询抄表记录
     * 时间格式: date('Y-m-d')
     */
    public function readRecordsByDate($startDate, $endDate)
    {
        return $this->request('findReadInfoByDate.do', [
            'startDate' => $startDate,
            'endDate'   => $endDate,
        ]);
    }


    /**
     * 根据时间(年月日时分秒)查询抄表记录
     * 时间格式: date('Y-m-d H:i:s')
     */
    public function readRecordsByTime($startTime, $endTime)
    {
        return $this->request('findReadInfoByDateTime.do', [
            'startDateTime' => $startTime,
            'endDateTime'   => $endTime,
        ]);
    }

    /**
     * 注册房源信息
     */
    public function registerRoomInfo(array $data)
    {
        return $this->request('registRoomInfo.do', [
            'roomInfo' => json_encode($data, JSON_UNESCAPED_UNICODE)
        ]);
    }
}