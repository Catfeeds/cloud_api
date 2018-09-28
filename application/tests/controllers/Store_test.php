<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/28
 * Time: 14:35
 */
class Store_test extends TestCase
{

    public function setUp()
    {
        $this->m_jwt = new M_jwt();
        $bxid = 99;
        $company_id = 1;
        $token = $this->m_jwt->generateJwtToken($bxid, $company_id);
        $this->request->setHeader('token', $token);
        // reset_instance();
        $this->request->enableHooks();
    }


    public function test_getStore()
    {
        $output = $this->request('POST',
            'employee/employee/showmystores',
            ['city' => '深圳市']
        );

        $this->assertResponseCode(200);
    }
}