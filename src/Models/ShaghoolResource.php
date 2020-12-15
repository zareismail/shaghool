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
}
