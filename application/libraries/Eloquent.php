<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Pagination\Paginator;

/**
 * Author:      weijinlong
 * Date:        2018/4/8
 * Time:        09:11
 * Describe:    在CI中引入Lavavel ORM的初始化代码
 */
class Eloquent
{
    private $capsule;


    public function __construct()
    {
        // var_dump(config_item('eloquent'));exit;
        $this->capsule = new Capsule();
        $this->capsule->addConnection(config_item('eloquent'));
        $this->capsule->setEventDispatcher(new Dispatcher(new Container));
        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();

        //在debug模式下全局记录sql到日志，方便调试
        Capsule::listen(function($event){
            $sql = str_replace("?", "'%s'", $event->sql);
            $log = vsprintf($sql, $event->bindings);
            log_message('debug',$log);
        });
    }
}
