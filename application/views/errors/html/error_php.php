<?php
defined('BASEPATH') OR exit('No direct script access allowed');
headers_sent() or header("Content-Type:application/json;charset=UTF-8");
?>
{ "rescode": 500, "resmsg": "语法错误", "data": [] }
<?php
exit;