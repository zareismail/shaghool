<?php

namespace Zareismail\Shaghool\Nova; 

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laravel\Nova\{Nova, TrashedStatus}; 
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\{ID, Number, Select, Currency, DateTime, BelongsTo/*, MorphTo*/, HasMany}; 
use DmitryBubyakin\NovaMedialibraryField\Fields\Medialibrary;
use Zareismail\NovaContracts\Nova\User;
use Armincms\Fields\Chain;  
use Zareismail\Shaghool\Helper;
use Zareismail\Fields\MorphTo;

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
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'measurable_type', 'period'
    ]; 

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

            BelongsTo::make(__('Reported By'), 'auth', User::class)
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
                ->withoutTrashed(), 

            Select::make(__('Reporting Period'), 'period')
                ->options(Helper::periods())
                ->default(Helper::MONTHLY),

            // Chain::make('periods', function() {
            //     return [ 
            //         Select::make(__('Reporting Period'), 'period')
            //             ->options(Helper::periods())
            //             ->default(Helper::MONTHLY)
            //     ];
            // }),

            // Chain::with('periods', function($request) {
            //     switch ($request->get('period', $this->period)) {
            //         case Helper::DAILY:
            //             return [
            //                 Select::make(__('Which Hour'), 'due')
            //                     ->options(array_combine(range(1, 24), range(1, 24))),
            //             ];
            //             break;

            //         case Helper::WEEKLY:
            //             return [
            //                 Select::make(__('Which Day'), 'due')
            //                     ->options(Helper::getDays())
            //                     ->default(Carbon::getWeekStartsAt()),
            //             ];
            //             break;

            //         case Helper::MONTHLY:
            //             return [
            //                 Select::make(__('Which Day'), 'due')
            //                     ->options(array_combine(range(1, 30), range(1, 30))),
            //             ];
            //             break;

            //         case Helper::YEARLY:
            //             return [
            //                 Select::make(__('Which Month'), 'due')
            //                     ->options(Helper::getMonths()),
            //             ];
            //             break;
                    
            //         default:
            //         return [];
            //             break;
            //     } 
            // }),  

            Number::make(__('Balance'), 'balance')
                ->required()
                ->rules('required')
                ->default(0),  

            HasMany::make(__('Consumption Reports'), 'reports', ConsumptionReport::class),
    	];
    }

    /**
     * Build an "index" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $search
     * @param  array  $filters
     * @param  array  $orderings
     * @param  string  $withTrashed
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function buildIndexQuery(NovaRequest $request, $query, $search = null,
                                      array $filters = [], array $orderings = [],
                                      $withTrashed = TrashedStatus::DEFAULT)
    {  
        $queryCallback = function($query) use ($request) {
            $query->orWhereHasMorph('measurable', Helper::morphs(), function($query, $type) use ($request) { 
                forward_static_call([Nova::resourceForModel($type), 'indexQuery'], $request, $query);
            });
        };

        return parent::buildIndexQuery($request, $query, $search, $filters, $orderings, $withTrashed)
                ->when(static::shouldAuthenticate($request, $query), $queryCallback);
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
        return $query->tap(function($query) use ($request) { 
            $query->with('measurable', function($morphTo) {
                return $morphTo->morphWith(Helper::morphs())->withTrashed();
            }); 

            $query->with('resource', function($query) {
                return $query->withTrashed();
            }); 
        });
    }

    /**
     * Get the value that should be displayed to represent the resource.
     *
     * @return string
     */
    public function title()
    {
        return $this->resourceTitle() .':'. $this->measurableTitle(); 
    }

    /**
     * Get the resource value that should be displayed to represent the resource.
     *
     * @return string
     */
    public function resourceTitle()
    {
        if(! isset($this->resource->resource)) {
            return __('Measurable :resource', [
                'resource' => $this->resource_id
            ]);
        }

        return with(new MeasurableResource($this->resource->resource), function($resource) {
            return $resource->title();
        }); 
    }

    /**
     * Get the measurable value that should be displayed to represent the resource.
     *
     * @return string
     */
    public function measurableTitle()
    {  
        return with(Nova::resourceForModel($this->measurable), function($resource) {
            if(is_null($resource)) {
                return __('Measuring :resource', [
                    'resource' => $this->measurable_type,
                ]);
            }

            return with(new $resource($this->measurable), function($resource) {
                return $resource->title();
            });  
        }); 
    }

    /**
     * Apply the search query to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function applySearch($query, $search)
    {   
        return parent::applySearch($query, $search)->orWhere(function($query) use ($search) {
            $query->orWhereHasMorph('measurable', Helper::morphs(), function($morphTo, $type) use ($search) {
                $morphTo->where(function($query) use ($type, $search) {
                    $resource = Nova::resourceForModel($type);

                    foreach ($resource::searchableColumns() as $column) {
                        $query->orWhere($query->qualifyColumn($column), 'like', '%'.$search.'%');
                    }  

                    if(method_exists($resource, 'applyPerCapitaSearch')) {
                        $resource::applyContractSearch($query, $search);  
                    }
                });
            }); 
        });
    }
}
