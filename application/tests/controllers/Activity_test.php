<?php

class ControllerDemo_test extends TestCase
{

    public function setUp()
    {
        $this->m_jwt=new M_jwt();
        $bxid       = 99;
        $company_id = 1;
        $token = $this->m_jwt->generateJwtToken($bxid, $company_id);
        $this->request->setHeader('token', $token);
        // reset_instance();
        $this->request->enableHooks();
    }

    public function test_AddAttract()
    {
        $data   = [
            'name'  => '吸粉活动',
            'image' => 'http://tfunx.oss-cn-shenzhen.aliyuncs.com/2018-09-27/5bac45eeac9d7.png',
            'start_time'    => '2018-07-01',
            'end_time'      => '2018-12-31',
            'rule'          => '吸粉活动规则',
            'description'   => '吸粉活动详情',
            'prizes'        => [
                [
                    'count'   => '100',
                    'limit'   => '10',
                    'single'  => '3',
                    'coupontype_id'   => 50,
                ],
                [
                    'count'   => '200',
                    'limit'   => '20',
                    'single'  => '6',
                    'coupontype_id'   => 52,
                ],
            ],
        ];
        $route  = 'activity/activity/addattractactivity';
        $output = $this->request(	'POST',
            $route,
            $data
        );
        $this->assertResponseCode(200);
    }
}
