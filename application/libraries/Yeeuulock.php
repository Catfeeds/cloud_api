<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use GuzzleHttp\Client;
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/9
 * Time:        10:47
 * Describe:    云柚LOCK
 */

class Yeeuulock
{
    protected $deviceId;
    private $partnerId;
    private $timeStamp;
    private $secret;
    private $apiBaseUrl;
    private $almsUrl;

    public function __construct($deviceId = null)
    {
        $this->deviceId     = $deviceId;
        $this->nonstr       = str_random(16);
        $this->timeStamp    = time();
        $this->apiBaseUrl   = 'https://api.yeeuu.com/v1/locks';
        $this->almsUrl      = 'https://alms.yeeuu.com/apartments/synchronize_apartments';
        $this->partnerId    = config_item('joyLockPartnerId');
        $this->secret       = config_item('joyLockSecret');
    }

    /**
     * 开锁
     */
    public function open()
    {
        return $this->httpPost($this->apiBaseUrl, [
            'key'       => $this->secret,
            'sn'        => $this->deviceId,
            'action'    => 'open',
        ]);
    }

    /**
     * 获取锁的状态
     */
    public function getStatus()
    {
        return $this->httpGet(implode('/', [$this->apiBaseUrl, $this->deviceId, 'getState']), [
            'key'   => $this->secret,
        ]);
    }

    /**
     * 新增/修改密码
     */
    public function extPwd($pwd, $type)
    {
        $pwdLength  = strlen($pwd);

        if (6 > $pwdLength OR 10 < $pwdLength OR !is_numeric($pwd)) {
            throw new \Exception('密码要求是6-10位的数字');
        }

        if (!in_array($type, [1, 2])) {
            throw new \Exception('不存在的密码类型');
        }

        return $this->httpGet(implode('/', [$this->apiBaseUrl, $this->deviceId, 'ext_password']), [
            'key'       => $this->secret,
            'password'  => $pwd,
            'type'      => $type,
        ]);
    }


    /**
     * 删除密码, 应该是每个锁有那么50个可以使用的密码, 删除指定的密码
     */
    public function rmPwd($index)
    {
        return $this->httpGet(implode('/', [$this->apiBaseUrl, $this->deviceId, 'operation_password']), [
            'key'       => $this->secret,
            'mode'      => '2',
            'index'     => $index,
        ]);
    }


    /**
     * 锁死/解锁密码
     */
    public function switchPwd($index, $action = 0)
    {
        return $this->httpGet(implode('/', [$this->apiBaseUrl, $this->deviceId, 'modify_password_property']), [
            'key'       => $this->secret,
            'action'    => $action,
            'index'     => $index,
        ]);
    }


    /**
     * 查询动态密码
     */
    public function cyclePwd()
    {
        return $this->httpGet(implode('/', [$this->apiBaseUrl, $this->deviceId, 'query_cycle_password']), [
            'key'   => $this->secret,
        ]);
    }


    /**
     * 查询门锁的开门记录
     * 日期格式: date('Ymd')
     */
    public function openRecords($startDate, $endDate)
    {
        $res = $this->httpGet(implode('/', [$this->apiBaseUrl, $this->deviceId, 'logs', $startDate, $endDate]), [
            'key'   => $this->secret,
        ]);
        return $res;
    }


    /**
     * 发送 POST 请求
     */
    public function httpPost($url, $options = [])
    {
        return $this->request($url, 'POST', $options);
    }


    /**
     * 发送 GET 请求
     */
    private function httpGet($url, $options = [])
    {
        return $this->request($url, 'GET', $options);
    }


    /**
     * 发送请求
     */
    private function request($url, $method, $options)
    {
        if ('POST' == $method) {
            $parameters     = ['form_params' => $options];
        } elseif ('GET' == $method) {
            $parameters     = ['query' => $options];
        }

        $res    = (new Client())->request($method, $url, $parameters)->getBody()->getContents();

        return json_decode($res, true);
    }


    /**
     * 同步房源
     */
    public function synchronizeApartments($data)
    {
        $time   = time();
        $nonstr = str_random(9);
        $token  = sha1($time . $this->secret . $nonstr);
        $url    = 'https://alms.yeeuu.com/apartments/synchronize_apartments';

        $res    = (new Client())->request('POST', $url, [
            'headers'      => [
                'Content-Type'  => 'application/json',
            ],
            'form_params'  => [
                'partnerId'     => $this->partnerId,
                'timestamp'     => $time,
                'nonstr'        => $nonstr,
                'token'         => $token,
                'apartmentList' => $data,
            ],
        ])->getBody()->getContents();
        return json_decode($res, true);
    }
}