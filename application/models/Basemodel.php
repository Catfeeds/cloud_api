<?php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Basemodel extends Model{

    public function __construct()
    {
        parent::__construct();
    }
    use SoftDeletes;
    protected $dates = ['deleted_at'];
}