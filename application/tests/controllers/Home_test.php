<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/25
 * Time: 15:24
 */

class Home_test extends TestCase
{

    public function setUp()
    {
        $this->m_jwt = new M_jwt();
        $bxid        = 99;
        $company_id  = 1;
        $token       = $this->m_jwt->generateJwtToken($bxid, $company_id);
        $this->request->setHeader('token', $token);
        // reset_instance();
        $this->request->enableHooks();
    }


    public function test_lists()
    {
        $output = $this->request('POST',
            'mini/home/lists');

        $this->assertResponseCode(200);

    }
}
