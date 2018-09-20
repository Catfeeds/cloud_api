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
		$output = $this->request(	'POST', 
									'shop/goods/listgoods',
									['page' => 2]
								);
	 	$this->assertResponseCode(200);
	}
}
