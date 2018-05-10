<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/10
 * Time:        14:16
 * Describe:    蛋贝
 */
class Danbay
{
    protected $deviceId;
    protected $token;
    private $baseUrl    = 'http://www.danbay.cn/system/';

    const PWD_TYPE_GUEST    = 3;
    const PWD_TYPE_BUTLER   = 2;
    const PWD_TYPE_TEMP     = 0;

    public function __construct($deviceId)
    {
        $this->deviceId = $deviceId;
    }

    /**
     * 增加临时密码
     */
    public function addTempPwd()
    {
        $pwd    = mt_rand(100000, 999998);
        $res    = $this->sendRequet('deviceCtrl/lockPwd/addPwd',[
            'password'  => $pwd,
            'pwdType'   => 0,
        ]);

        return [
            'pwd_id'   => $res['pwdID'],
            'password' => $pwd,
        ];
    }

    /**
     * 新的房客随机密码
     */
    public function newRandomGuestPwd()
    {
        $pwd = mt_rand(100000, 999998);

        $res = $this->sendRequet('deviceCtrl/lockPwd/addPwd',[
            'password'  => $pwd,
            'pwdType'   => self::PWD_TYPE_GUEST,
        ]);

        return [
            'pwd_id'   => $res['pwdID'],
            'password' => $pwd,
        ];
    }

    /**
     * 清除所有的房客密码
     */
    public function clearAllGuestPwd()
    {
        collect($this->getLockPwdList())->where('pwdType', self::PWD_TYPE_GUEST)
            ->each(function ($item) {
                return $this->removePwd($item['pwdType'], $item['pwdID']);
            });

        return true;
    }

    /**
     * 编辑指定密码
     */
    public function editGuestPwd($pwdID, $newPwd)
    {
        $res = $this->sendRequet('deviceCtrl/lockPwd/editPwd', [
            'pwdType'   => 3,
            'password'  => $newPwd,
            'pwdID'     => $pwdID,
        ]);
    }

    /**
     * 移除指定密码
     */
    public function removePwd($pwdType, $pwdID)
    {
        return $this->sendRequet('deviceCtrl/lockPwd/delPwd', [
            'pwdType'   => $pwdType,
            'pwdID'     => $pwdID,
        ], 'POST', true);
    }

    /**
     * 获取指定门锁的密码列表
     */
    public function getLockPwdList()
    {
        return $this->sendRequet('deviceInfo/getLockPwdList');
    }

    /**
     * 向蛋贝服务器发送请求
     */
    private function sendRequet($uri, $options = [], $method = 'POST', $enctypeMultipart = false)
    {
        $res = (new Client())->request(
            $method,
            $this->baseUrl . $uri,
            $this->buildRequestBody($options, $enctypeMultipart)
        )->getBody()->getContents();

        $res = json_decode($res, true);

        if (200 != $res['status']) {
            throw new \Exception($res['message']);
        }

        return $res['result'];
    }

    /**
     * 构建请求体
     */
    private function buildRequestBody($options, $enctypeMultipart = false)
    {
        $form = collect($options)
            ->put('deviceId', $this->deviceId)
            ->put('mtoken', $this->getToken())
            ->when($enctypeMultipart, function ($items) {
                return $items->transform(function ($item, $key) {
                    return [
                        'name'  => $key,
                        'contents' => $item,
                    ];
                })->values();
            })->toArray();

        $formKey = $enctypeMultipart ? 'multipart' : 'form_params';

        return [$formKey => $form];
    }

    /**
     * 从缓存中获取 token
     */
    private function setToken()
    {
        $token = Cache::get(config('strongberry.danbayTokenKey'));

        if (!$token) {
            throw new \Exception('token 过期,请稍后重试!');
        }

        $this->token = $token;

        return $this;
    }

    /**
     * 获取请求凭证 token
     */
    private function getToken()
    {
        if ($this->token) {
            return $this->token;
        }

        $this->setToken();

        return $this->token;
    }
}