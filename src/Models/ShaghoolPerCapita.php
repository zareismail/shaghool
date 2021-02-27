<?php

namespace Zareismail\Shaghool\Models;

use Zareismail\NovaContracts\Models\AuthorizableModel; 

class ShaghoolPerCapita extends AuthorizableModel 
{     
    /**
     * Bootstrap the model and its traits.
     *
     * @return void
     */
    protected static function boot()
    { 
    	parent::boot();
    	
        static::deleting(function($model) {
            $method = $model->isForceDeleting() ? 'forceDelete' : 'delete';

             $model->reports()->{$method}();
        }); 

        static::restored(function($model) {
            $model->reports()->onlyTrashed()->restore();
        }); 
    }

	/**
	 * Query the related ShaghoolResource.
	 * 
	 * @return  \Illuminate\Database\Eloquent\Relations\BelongsTo     
	 */
	public function resource()
	{ 
		return $this->belongsTo(ShaghoolResource::class);
	}

	/**
	 * Query the related measurable.
	 * 
	 * @return  \Illuminate\Database\Eloquent\Relations\MorphTo     
	 */
	public function measurable()
	{ 
		return $this->morphTo();
	} 

	/**
	 * Query the related ShaghoolReport.
	 * 
	 * @return  \Illuminate\Database\Eloquent\Relations\HasMany     
	 */
	public function reports()
	{ 
		return $this->hasMany(ShaghoolReport::class, 'percapita_id');
	}  
}
