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
    	'report_date' => 'datetime',
    ];

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
