<?php
namespace Servit\Model;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BaseModel extends Model  {

        public static function boot()     {
           parent::boot();
           static::creating(function($model){
               //dump('creating');
           }); 
           static::created(function($model){
               //dump('created');
           }); 
           static::updating(function($model){
               //dump('updating');
           }); 
           static::updated(function($model){
               //dump('updated');
           });
           static::saving(function($model){
               //dump('saving');
           }); 
           static::saved(function($model){
               //dump('saved');
           }); 
           static::loaded(function($model){
               //dump('load');
           }); 
        }
        
        public function newFromBuilder($attributes = array(),$connection = null) {
           $instance = parent::newFromBuilder($attributes);
           $instance->fireModelEvent('loaded');
           return $instance;
        }
        public static function loaded($callback, $priority = 0)    {
           static::registerModelEvent('loaded', $callback, $priority);
        }

        //--- public function in model want start getCapitalname---
        public function getTableColumns() {
            return $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
        }

}