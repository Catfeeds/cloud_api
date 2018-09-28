<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/26
 * Time: 14:20
 */
class PriceControl_test extends TestCase
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
    // public function test_toexcel()
    // {
    //     $data = ['store_id' => 1,
    //              'url'      => 'http://tfunx.oss-cn-shenzhen.aliyuncs.com/2018-09-27/5bac7104cc7b5.xlsx'];
    //     $output = $this->request('POST', 'pricecontrol/pricecontrol/importprice', $data);
    //     $this->assertEquals(99, get_instance()->current_id);
    //     $this->assertResponseCode(200);
    // }
}