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
	
	
	public function test_meterOfStore()
	{
		$output = $this->request('POST',
			'utility/meter/meterofstore',
			['room_id' => 1]
		);
		
		$this->assertResponseCode(200);
	}
	
	/*public function test_utility()
	{
		$output = $this->request('POST',
			'utility/meter/utility',
			['store_id' => 1,
			 'type'     => 'COLD_WATER_METER',
			 'year'     => 2018,
			 'month'    => 11]
		);
		
		$this->assertResponseCode(200);
		
	}*/
}
