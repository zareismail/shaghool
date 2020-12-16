<?php

namespace Zareismail\Shaghool\Nova; 

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laravel\Nova\Nova; 
use Laravel\Nova\Fields\{ID, Number, Select, Currency, DateTime, BelongsTo, MorphTo, HasMany}; 
use DmitryBubyakin\NovaMedialibraryField\Fields\Medialibrary;
use Zareismail\NovaContracts\Nova\User;
use Armincms\Fields\Chain;  
use Zareismail\Shaghool\Helper;

class PerCapita extends Resource
{  
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \Zareismail\Shaghool\Models\ShaghoolPerCapita::class;

    /**
     * The relationships that should be eager loaded when performing an index query.
     *
     * @var array
     */
    public static $with = ['resource', 'auth'];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
    	return [
    		ID::make(), 

            BelongsTo::make(__('Reporter'), 'auth', User::class)
                ->withoutTrashed() 
                ->searchable()
                ->debounce(100)
                ->canSee(function($request) {
                    return $request->user()->can('forceDelete', static::newModel());
                }),  

            BelongsTo::make(__('Measurable Resource'), 'resource', MeasurableResource::class)
                ->withoutTrashed(),

            MorphTo::make(__('Measuring'), 'measurable')
                ->types(Helper::measurableResources($request)->all())
                ->withoutTrashed()
                ->searchable()
                ->debounce(100), 

            Chain::make('periods', function() {
                return [ 
                    Select::make(__('Reporting Period'), 'period')
                        ->options(Helper::periods())
                        ->default(Helper::MONTHLY)
                ];
            }),

            Chain::with('periods', function($request) {
                switch ($request->get('period', $this->period)) {
                    case Helper::DAILY:
                        return [
                            Select::make(__('Which Hour'), 'due')
                                ->options(array_combine(range(1, 24), range(1, 24))),
                        ];
                        break;

                    case Helper::WEEKLY:
                        return [
                            Select::make(__('Which Day'), 'due')
                                ->options(Helper::getDays())
                                ->default(Carbon::getWeekStartsAt()),
                        ];
                        break;

                    case Helper::MONTHLY:
                        return [
                            Select::make(__('Which Day'), 'due')
                                ->options(array_combine(range(1, 30), range(1, 30))),
                        ];
                        break;

                    case Helper::YEARLY:
                        return [
                            Select::make(__('Which Month'), 'due')
                                ->options(Helper::getMonths()),
                        ];
                        break;
                    
                    default:
                    return [];
                        break;
                } 
            }),

            Number::make(__('Number of reports'), 'duration')
                ->rules('required')
                ->required()
                ->default(1)
                ->min(1), 

            Number::make(__('Balance'), 'balance')
                ->required()
                ->rules('required')
                ->default(0), 

            // DateTime::make(__('Start Date'), 'start_date')
            //     ->required()
            //     ->rules('required'),  

            HasMany::make(__('Consumption Reports'), 'reports', ConsumptionReport::class),
    	];
    }

    /**
     * Get the value that should be displayed to represent the resource.
     *
     * @return string
     */
    public function title()
    {
        $resource = Nova::resourceForModel($this->measurable);

        return  (new $resource($this->measurable))->title().': '.
        (new MeasurableResource($this->resource->resource))->title();
    }
}