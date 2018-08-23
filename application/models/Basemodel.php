<?php
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\SoftDeletes;

//需要追加company_id的表
define('SAASWHITELIST',['table1','table2']);

class CompanyScope implements Scope {
    public function apply(Builder $builder, Model $model) {
        // var_dump($model->getTable());exit;
        
        if (in_array($model->getTable(), SAASWHITELIST)) {
            return $builder->where('company_id', '=', COMPANY_ID);
        } else {
            return $builder;
        }

    }
}

class UserObserver {
    public function creating($user) {
        if (in_array($user->getTable(), SAASWHITELIST)) {
            $user->company_id = COMPANY_ID;
        }
    }
    public function updating($user) {
        if (in_array($user->getTable(), SAASWHITELIST)) {
            $user->company_id = COMPANY_ID;
        }
    }
    public function saving($user) {
        if (in_array($user->getTable(), SAASWHITELIST)) {
            $user->company_id = COMPANY_ID;
        }
    }
    public function deleting($user) {
        if (in_array($user->getTable(), SAASWHITELIST)) {
            $user->company_id = COMPANY_ID;
        }
    }
    public function restoring($user) {
        if (in_array($user->getTable(), SAASWHITELIST)) {
            $user->company_id = COMPANY_ID;
        }
    }

}

class Basemodel extends Model {

    use SoftDeletes;
    protected $dates = ['deleted_at'];

    //public static $where = ['cid'=>CURRENT_ID];
    protected static function boot() {
        parent::boot();
        static::addGlobalScope(new CompanyScope);
        static::observe(UserObserver::class);
    }
}
