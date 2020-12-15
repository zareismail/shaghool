<?php

namespace Zareismail\Shaghool\Contracts;

use Illuminate\Database\Eloquent\Relations\HasOneOrMany; 

interface Measurable
{
	/**
	 * Query the related details.
	 * 
	 * @return \Illuminate\Database\Eloquent\Relations\HasOneOrMany
	 */
	public function contracts(): HasOneOrMany; 
} 