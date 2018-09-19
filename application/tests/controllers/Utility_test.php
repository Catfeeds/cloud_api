<?php

class Utility_test extends TestCase
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
	
	
	public function test_readingTemplate()
	{
		$output = $this->request('POST',
			'utility/utility/readingtemplate',
			['store_id','type']
		);
		
		$this->assertResponseCode(200);
		
	}
}
