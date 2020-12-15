<?php

namespace Zareismail\Shaghool\Nova; 

use Illuminate\Http\Request; 
use Laravel\Nova\Fields\{ID, Text, HasMany}; 

class MeasurableResource extends Resource
{  
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \Zareismail\Shaghool\Models\ShaghoolResource::class;

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
    	return [
    		ID::make()->sortable(),   

            Text::make(__('Resource Name'), 'name')
                ->sortable()
                ->required()
                ->rules('required'),

            Text::make(__('Note'), 'note')
                ->nullable(), 

            HasMany::make(__('Per Capitas'), 'percapitas', PerCapita::class),
    	];
    }  
}