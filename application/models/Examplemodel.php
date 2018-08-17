<?php
use Illuminate\Database\Eloquent\Model;

class Examplemodel extends Basemodel {
    /**
     * Examplemodel constructor
     */

    public function __construct() {
        parent::__construct();
    }
//    表名（必须）
    protected $table = 'users';
//    主键（不必须，默认是id）
    protected $primaryKey = 'id';
//    允许批量赋值（不必须）
    protected $fillable = ['name', 'nickname'];
//    不允许批量赋值（不必须）
    protected $guarded = [];
//    是否自动更新created_at 和updated_at（不必须，默认打开）
    protected $timestamps = true;
//    转换成json或array时隐藏的属性（不必须）
    protected $hidden = ['created_at', 'updated_at'];
}
