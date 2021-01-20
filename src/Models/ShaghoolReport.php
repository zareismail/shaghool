<?php

namespace Zareismail\Shaghool\Models;

use Zareismail\NovaContracts\Models\AuthorizableModel; 

class ShaghoolReport extends AuthorizableModel 
{   
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
    	'target_date' => 'datetime',
    ];

    /**
     * Bootstrap the model and its traits.
     *
     * @return void
     */
    public static function boot()
    {
    	parent::boot();

    	static::saving(function($model) { 
    		$model->forceFill([
    			'balance' => optional(ShaghoolPerCapita::find($model->percapita_id))->balance
    		]);
    	});
    }

	/**
	 * Query the related ShaghoolPerCapita.
	 * 
	 * @return  \Illuminate\Database\Eloquent\Relations\BelongsTo     
	 */
	public function percapita()
	{ 
		return $this->belongsTo(ShaghoolPerCapita::class);
	} 
}
