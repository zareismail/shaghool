<?php

namespace Zareismail\Shaghool\Models;

use Zareismail\NovaContracts\Models\AuthorizableModel; 

class ShaghoolPerCapita extends AuthorizableModel 
{   
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
    	'start_date' => 'datetime',
    ];

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