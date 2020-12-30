<?php

namespace Zareismail\Shaghool\Nova; 

use Illuminate\Http\Request; 
use Laravel\Nova\Fields\{ID, Text, BelongsTo, HasMany}; 
use Zareismail\Keil\Nova\MeasuringUnit; 

class MeasurableResource extends Resource
{  
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \Zareismail\Shaghool\Models\ShaghoolResource::class;

    /**
     * The relationships that should be eager loaded when performing an index query.
     *
     * @var array
     */
    public static $with = ['unit'];

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
            
            BelongsTo::make(__('Measuring Unit'), 'unit', MeasuringUnit::class)
                ->withoutTrashed(),

            Text::make(__('Resource Name'), 'name')
                ->sortable()
                ->required()
                ->rules('required'),

            Text::make(__('Note'), 'note')
                ->nullable(),

            HasMany::make(__('Per Capitas'), 'percapitas', PerCapita::class),
    	];
    }   

    /**
     * Get the value that should be displayed to represent the resource.
     *
     * @return string
     */
    public function title()
    {
        return $this->name.' ('.$this->unit->symbol.')';
    }

    /**
     * Get the cards available on the entity.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [
        ];
    }
}