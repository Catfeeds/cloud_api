<?php
/**
 * Part of ci-phpunit-test
 *
 * @author     Kenji Suzuki <https://github.com/kenjis>
 * @license    MIT License
 * @copyright  2015 Kenji Suzuki
 * @link       https://github.com/kenjis/ci-phpunit-test
 */

class Welcome_test extends TestCase
{
	
	public function setUp()
    {
		$this->m_jwt=new M_jwt();
		$bxid       = 99;
        $company_id = 1;
		$token = $this->m_jwt->generateJwtToken($bxid, $company_id);
		$this->request->setHeader('token', $token);
		reset_instance();
		$this->request->enableHooks();
	
		
		// $this->request->setHeader('token', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9mdW54ZGF0YS5jb20iLCJleHAiOjE1MzY4Mzg4NzQsIm5iZiI6bnVsbCwiYnhpZCI6OTksImNvbXBhbnlfaWQiOjF9.qzliK6EiriSOAluPc9STVFGWHY0_L7ygGTCH2Qu6ifY');
	}



	public function test_company()
	{
		$output = $this->request('GET', 'ping/index');
		$this->assertContains('7123456', $output);
	}

	public function test_listgoods1()
	{
		$output = $this->request('POST', 'shop/goods/listgoods');
		$this->assertEquals(99, get_instance()->current_id);
		$this->assertResponseCode(200);
	}

	public function test_listgoods2()
	{
		$output = $this->request('POST', 'shop/goods/listgoods');
	 $this->assertResponseCode(200);
	}
}
