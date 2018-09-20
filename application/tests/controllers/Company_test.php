<?php

class Company_test extends TestCase
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


    public function test_companyInfo1(){
        $output = $this->request(	'POST', 
									'company/company/companyInfo'
                                );
                                
         $this->assertResponseCode(200);
        
    }

    public function test_companyInfo2(){
        $output = $this->request(	'POST', 
									'company/company/companyInfo'
                                );
                                
         $data = json_decode($output,true); 
         $this->assertEquals(0,$data['rescode']);
        
    }

    


}
