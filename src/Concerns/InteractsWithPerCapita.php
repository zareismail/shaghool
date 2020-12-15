<?php

namespace Zareismail\Shaghool\Concerns; 

use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Zareismail\Shaghool\Models\ShaghoolPerCapita;

trait InteractsWithPerCapita
{ 
	/**
	 * Query the related contracts.
	 * 
	 * @return \Illuminate\Database\Eloquent\Relations\HasOneOrMany
	 */
	public function perCapitas(): HasOneOrMany
	{
		return $this->morphMany(ShaghoolPerCapita::class, 'measurable');
	}
} 