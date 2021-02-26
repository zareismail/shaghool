<?php

namespace Zareismail\Shaghool\Nova; 

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laravel\Nova\TrashedStatus;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\{ID, Text, Number, Select, Date, BelongsTo, MorphTo, HasOneThrough, HasMany}; 
use DmitryBubyakin\NovaMedialibraryField\Fields\Medialibrary;
use Armincms\Fields\{CHain, InputSelect};  
use Zareismail\NovaContracts\Nova\User;
use Zareismail\Shaghool\Helper;

class ConsumptionReport extends Resource
{  
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \Zareismail\Shaghool\Models\ShaghoolReport::class;

    /**
     * The relationships that should be eager loaded when performing an index query.
     *
     * @var array
     */
    public static $with = ['percapita.resource.unit', 'auth'];

    /**
     * Indicates if the resource should be globally searchable.
     *
     * @var bool
     */
    public static $globallySearchable = false;

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

            BelongsTo::make(__('Per Capita'), 'percapita', PerCapita::class)
                ->withoutTrashed()
                ->inverse('reports'),  

            BelongsTo::make(__('Reported By'), 'auth', User::class)
                ->withoutTrashed() 
                ->searchable()
                ->debounce(100)
                ->readonly(! is_null($capita = $this->percapita ?: $request->findParentModel()))
                ->default($this->auth_id ?? $capita->auth_id ?? $request->user()->id)
                ->canSee(function($request) {
                    return $request->user()->can('update', static::newModel());
                }),  

            $this->mergeWhen($capita, function() use ($capita, $request) {
                return [   
                    Number::make(__('Balance'), 'value')
                        ->required()
                        ->rules('required')
                        ->readonly($request->isMethod('get'))
                        ->hideFromIndex()
                        ->withMeta([
                            'value' => $capita->balance
                        ]),

                    Number::make(__('Wastage'), 'value', function($value) use ($capita) {
                            return $value - $capita->balance;
                        })->exceptOnForms(),
                ];
            }),

            Number::make(__('Consumption Value'), 'value')
                ->required()
                ->rules('required'), 
                    
            Number::make(__('Measuring Unit'), function() {
                return data_get($this->percapita, 'resource.unit.name');
            }),

            Date::make(__('Target Date'), 'target_date')
                ->required()
                ->rules('required'), 
    	];
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
            // Metrics\WastagePerResources::make().
        ];
    } 

    /**
     * Build an "index" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        $query->with('percapita', function($query) use ($request) {
            PerCapita::buildIndexQuery($request, $query);
        });
    }

    /**
     * Authenticate the query for the given request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function authenticateQuery(NovaRequest $request, $query)
    {
        return $query->where(function($query) use ($request) {
            $query->when(static::shouldAuthenticate($request, $query), function($query) use ($request) {
                $query->authenticate()->orWhereHas('percapita', function($query) use ($request) {
                    PerCapita::buildIndexQuery($request, $query);
                });
            });
        });
    }
}
