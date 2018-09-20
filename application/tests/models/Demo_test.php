<?php

class Demo_test extends TestCase
{
    public function setUp()
    {
        $this->resetInstance();
        $this->CI->load->model('companymodel');
        // get_instance()->load->model('companymodel');
    }
    
    public function test_get_category_name()
    {
        $count = Companymodel::count();
        $this->assertGreaterThan(1,$count);
    }

    public function test_get_category_name2()
    {
        $data = Companymodel::all()->toArray();
        $this->assertArrayHasKey('name', $data[0]);
    }
}