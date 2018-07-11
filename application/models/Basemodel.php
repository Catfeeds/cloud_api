<?php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Scope;

class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        // var_dump($model->getTable());exit;
        if (strpos($model->getTable(),'boss_')===0){
            return $builder->where('company_id', '=', 10001);
        }else{
            return $builder;
        }
        
    }
}

class UserObserver
{
    public function creating($user)
    {
        $user->company_id=10001;
    }
    public function updating($user)
    {
        $user->company_id=10001;
    }
    public function saving($user)
    {
        $user->company_id=10001;
    }
    public function deleting($user)
    {
        $user->company_id=10001;
    }
    public function restoring($user)
    {
        $user->company_id=10001;
    }
    
}

class Basemodel extends Model{

    use SoftDeletes;
    protected $dates = ['deleted_at'];

    //public static $where = ['cid'=>CURRENT_ID];
    protected static function boot()
    {
        parent::boot();
        // static::addGlobalScope(new CompanyScope);
        // static::observe(UserObserver::class);
    }
}