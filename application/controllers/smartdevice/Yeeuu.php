<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/9
 * Time:        10:47
 * Describe:    云柚智能设备
 */

class Yeeuu
{
    private $partnerId;
    private $secret;
    private $apiBaseUrl;
    private $almsBaseUrl;

    public function __construct()
    {
        $this->apiBaseUrl   = 'https://api.yeeuu.com/v1/locks';
        $this->almsUrl      = 'https://alms.yeeuu.com/apartments/synchronize_apartments';
        $this->partnerId    = config_item('joyLockPartnerId');
        $this->secret       = config_item('joyLockSecret');
    }

    /**
     * 开锁
     */
    public function open($deviceNumber)
    {
        return httpPost($this->apiBaseUrl, [
            'key'       => $this->secret,
            'sn'        => $deviceNumber,
            'action'    => 'open',
        ]);
    }


    /**
     * 获取锁的状态
     */
    public function getStatus($sn)
    {
        return httpGet(implode('/', [$this->apiBaseUrl, $sn, 'getState']), [
            'key'   => $this->secret,
        ]);
    }


    /**
     * 新增/修改密码
     */
    public function extPwd($sn, $pwd, $type)
    {
        $pwdLength  = strlen($pwd);

        if (6 > $pwdLength OR 10 < $pwdLength OR !is_numeric($pwd)) {
            throw new \Exception('密码要求是6-10位的数字');
        }

        if (!in_array($type, [1, 2])) {
            throw new \Exception('不存在的密码类型');
        }

        return httpGet(implode('/', [$this->apiBaseUrl, $sn, 'ext_password']), [
            'key'       => $this->secret,
            'password'  => $pwd,
            'type'      => $type,
        ]);
    }


    /**
     * 删除密码, 应该是每个锁有那么50个可以使用的密码, 删除指定的密码
     */
    public function rmPwd($sn, $index)
    {
        return httpGet(implode('/', [$this->apiBaseUrl, $sn, 'operation_password']), [
            'key'       => $this->secret,
            'mode'      => '2',
            'index'     => $index,
        ]);
    }


    /**
     * 锁死/解锁密码
     */
    public function switchPwd($sn, $index, $action = 0)
    {
        return httpGet(implode('/', [$this->apiBaseUrl, $sn, 'modify_password_property']), [
            'key'       => $this->secret,
            'action'    => $action,
            'index'     => $index,
        ]);
    }


    /**
     * 查询动态密码
     */
    public function cyclePwd($sn)
    {
        return httpGet(implode('/', [$this->apiBaseUrl, $sn, 'query_cycle_password']), [
            'key'   => $this->secret,
        ]);
    }


    /**
     * 查询门锁的开门记录
     * 日期格式: date('Ymd')
     */
    public function openRecords($sn, $startDate, $endDate)
    {
        return httpGet(implode('/', [$this->apiBaseUrl, $sn, 'logs', $startDate, $endDate]), [
            'key'   => $this->secret,
        ]);
    }


    /**
     * 发送 POST 请求
     */
    private function httpPost($url, $options = [])
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
    /*private function request($url, $method, $options)
    {
        if ('POST' == $method) {
            $parameters     = ['form_params' => $options];
        } elseif ('GET' == $method) {
            $parameters     = ['query' => $options];
        }

        $res    = (new Client())->request($method, $url, $parameters)->getBody()->getContents();

        return json_decode($res, true);
    }*/
    /**
     * 发送HTTP请求
     *
     * @param string $url 请求地址
     * @param string $method 请求方式 GET/POST
     * @param string $refererUrl 请求来源地址
     * @param array $data 发送数据
     * @param string $contentType
     * @param string $timeout
     * @param string $proxy
     * @return boolean
     */
    private function request($url, $method,$data,  $contentType = 'application/json', $timeout = 30, $proxy = false) {
        $ch = null;
        if('POST' === strtoupper($method)) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HEADER,0 );
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            if($contentType) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:'.$contentType));
            }
            if(is_string($data)){
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        } else if('GET' === strtoupper($method)) {
            if(is_string($data)) {
                $real_url = $url. (strpos($url, '?') === false ? '?' : ''). $data;
            } else {
                $real_url = $url. (strpos($url, '?') === false ? '?' : ''). http_build_query($data);
            }

            $ch = curl_init($real_url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:'.$contentType));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        } else {
            $args = func_get_args();
            return false;
        }

        if($proxy) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }
        $ret = curl_exec($ch);
        $info = curl_getinfo($ch);
        $contents = array(
            'httpInfo'  => array(
                'send'  => $data,
                'url'   => $url,
                'ret'   => $ret,
                'http'  => $info,
            )
        );

        curl_close($ch);
        return json_decode($ret, true);
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

        $res    = $this->request($url,'POST',  [
            'form_params'  => [
                'partnerId'     => $this->partnerId,
                'timestamp'     => $time,
                'nonstr'        => $nonstr,
                'token'         => $token,
                'apartmentList' => $data,
            ],
        ]);

        return json_decode($res, true);
    }
}