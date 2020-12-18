<?php

namespace Zareismail\Shaghool\Models; 


class ShaghoolResource extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

	/**
	 * Query the related ShaghoolPerCapita`s.
	 * 
	 * @return  \Illuminate\Database\Eloquent\Relations\HasMany     
	 */
	public function percapitas()
	{ 
		return $this->hasMany(ShaghoolPerCapita::class, 'resource_id');
	}

	/**
	 * Query the related KeilUnit`s.
	 * 
	 * @return  \Illuminate\Database\Eloquent\Relations\BelongsTo     
	 */
	public function unit()
	{ 
		return $this->belongsTo(\Zareismail\Keil\Models\KeilUnit::class);
	}
}
