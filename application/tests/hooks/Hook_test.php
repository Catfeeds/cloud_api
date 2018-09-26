<?php

class Hook_test extends TestCase
{
	
	public function setUp()
    {

	}

	public function test_currentid()
	{
		$this->m_jwt=new M_jwt();
		$bxid       = 99;
        $company_id = 1;
		$token = $this->m_jwt->generateJwtToken($bxid, $company_id);
		$this->request->setHeader('token', $token);
		reset_instance();
        $this->request->enableHooks();
        $output = $this->request(	'POST', 
									'company/company/companyInfo'
                                );
		$this->assertEquals(99, get_instance()->current_id);
		$this->assertEquals(1, get_instance()->company_id);
	}

	public function test_xapitoken()
	{
		$apikey='111111111';
        $apisecret='nf239fh293hf8h23f';
        $timestamp=time();
        $hash=hash('sha256',"$apikey.$timestamp.$apisecret");
		$x_api_token = "$apikey.$timestamp.$hash";
		
		$this->request->setHeader('x-api-token', $x_api_token);
		reset_instance();
        $this->request->enableHooks();
        $output = $this->request(	'POST', 
									'innserservice/index/index'
								);
		$this->assertEquals('111111111', get_instance()->x_api_token);
		// $this->assertEmpty(get_instance()['current_id']);
		
	}
}
