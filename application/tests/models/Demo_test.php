<?php

class Demo_test extends TestCase
{
    public function setUp()
    {
        $this->resetInstance();
        $this->CI->load->model('companymodel');
    }
    
    public function test_get_category_name()
    {
        $count = Companymodel::count();
        $this->assertEquals(2, $count);
    }
}