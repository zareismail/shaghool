<?php

namespace Zareismail\Shaghool\Nova\Dashboards;

use Illuminate\Support\Str;
use Illuminate\Http\Resources\ConditionallyLoadsAttributes;
use Laravel\Nova\Dashboard;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\{Select, DateTime};
use Laravel\Nova\Nova;
use Coroowicaksono\ChartJsIntegration\LineChart; 
use Zareismail\Shaghool\Nova\MeasurableResource; 
use Zareismail\Shaghool\Helper; 
use Zareismail\Fields\Contracts\Cascade;

class ConsumptionReports extends Dashboard
{
    use ConditionallyLoadsAttributes;

    /**
     * Get the displayable name of the dashboard.
     *
     * @return string
     */
    public static function label()
    {
        return __('Consumption Reports');
    } 

    public function filters(NovaRequest $request)
    { 
        return $this->filter([  

            DateTime::make(__('From Date'), 'from_date', function($value) {
                if(! is_null($value)) {
                    return \Carbon\Carbon::create($value)->format('Y-m-d H:i:s.u'); 
                }
            })  
                ->nullable()
                ->help($request->filled('from_date') ? __('Filtered by :date', [
                    'date' => $request->get('from_date')
                ]) : '')
                ->withMeta([
                    'width' => 'w-1/2', 
                    'placeholder' => $request->get('to_date')
                ]),

            DateTime::make(__('To Date'), 'to_date', function($value) {
                if(! is_null($value)) {
                    return \Carbon\Carbon::create($value)->format('Y-m-d H:i:s.u'); 
                }
            })  
                ->nullable()
                ->help($request->filled('to_date') ? __('Filtered by :date', [
                    'date' => $request->get('to_date')
                ]) : '')
                ->withMeta([
                    'width' => 'w-1/2', 
                    'placeholder' => $request->get('to_date')
                ]),


            Select::make(__('Report Of'), 'measurable') 
                ->options(Helper::measurableResources($request)->mapWithKeys(function($resource) {
                    return [
                        $resource::uriKey() => $resource::label()
                    ];
                }))
                ->displayUsingLabels()
                ->nullable()
                ->withMeta([
                    'placeholder' => __('All')
                ]), 

            $this->mergeWhen($request->filled('measurable'), function() use ($request) {
                $resource = $this->findResourceForKey($request, $request->get('measurable'));

                return (array) $this->getFieldsForResource($request, $resource); 
            }),
        ]);
    }

    public function findResourceForKey($request, $key)
    {
        return Helper::measurableResources($request)->first(function($resource) use ($key) {
            return $resource::uriKey() == $key;
        });
    }

    public function getFieldsForResource($request, $resource)
    {
        $fields = []; 
        $viaResourceId = null;

        if($parent = $this->findParentForResource($resource)) {
            $fields = array_merge($fields, $this->getFieldsForResource(
                $request, $parent
            ));  
        }
        
        if(! is_null($parent) && ! $request->filled($this->resourceFilterKey($parent))) {
            return $fields;
        } elseif(! is_null($parent)) {
            $viaResourceId = intval($request->get($this->resourceFilterKey($parent)));
        }

        $selection = tap($this->getResourceSelection($request, $resource, $viaResourceId), function($field) {
            $measurable = $this->findResourceForKey(request(), request('measurable'));

            if($field->attribute == $this->resourceFilterKey($measurable)) {
                $field->nullable()->withMeta([
                    'placeholder' => __('All') 
                ]); 
            }
        });  

        array_push($fields, $selection); 

        return $fields;
    }

    /**
     * Get the parent resource of the given resource.
     * 
     * @param  string $resource 
     * @return string           
     */
    public function findParentForResource($resource)
    {
        if($resource::newModel() instanceof Cascade) {
            return Nova::resourceForModel($resource::newModel()->parent()->getModel());
        }  
    }

    /**
     * Get Resoruce item selction.
     * 
     * @param  \Laravel\Nova\Http\Requests\NovaRequest $request       
     * @param  string $resource      
     * @param  string $viaResourceId 
     * @return \LaravelNova\Fields\Field                
     */
    public function getResourceSelection($request, $resource, $viaResourceId)
    {
        return Select::make($resource::label(), $this->resourceFilterKey($resource)) 
                ->options($resource::newModel()->when($viaResourceId, function($query) use ($viaResourceId) {
                    return $query->whereHas('parent', function($query) use ($viaResourceId) {
                        $query->whereKey($viaResourceId);
                    });
                })->get()->keyBy('id')->mapInto($resource)->map->title())
                ->displayUsingLabels();
    }

    public function resourceFilterKey($resource)
    {
        return $resource::uriKey();
    }

    /**
     * Get the cards for the dashboard.
     *
     * @return array
     */
    public function cards()
    { 
        return MeasurableResource::newModel()->with([
            'percapitas' => function($query) {
                $query
                    ->with([
                        'reports' => function($query) {
                            $query ->when(request()->filled('from_date'), function($query) {
                                $query->where('target_date', '>=', request()->get('from_date'));
                            })
                            ->when(request()->filled('to_date'), function($query) {
                                $query->where('target_date', '<=', request()->get('to_date'));
                            });
                        }
                    ])
                    ->when(request('measurable'), function($query) {
                        $resource = Nova::resourceForKey(request('measurable'));

                        $queryCallback = function($query) use ($resource) {      
                            $query->when(
                                request()->filled($this->resourceFilterKey($resource)), 
                                function($query) use ($resource) {
                                    $query->whereKey(request()->input($this->resourceFilterKey($resource)));
                                }, 
                                function($query) use ($resource) {
                                    if($parent = $this->findParentForResource($resource)) {
                                        $query->whereHas('parent', function($query) use ($parent) {
                                            $query->whereKey(request()->input($this->resourceFilterKey(
                                                $parent
                                            )));
                                        });
                                    }
                                }
                            );
                        };

                        $query->whereHasMorph(
                            'measurable', [$resource::newModel()->getMorphClass()], $queryCallback
                        ); 
                    });
            }
        ])->get()->flatMap(function($resource) {
            $balance = $resource->percapitas->sum('balance');

            return [
                (new LineChart())
                    ->title((new MeasurableResource($resource))->title())
                    ->animations([
                        'enabled' => true,
                        'easing' => 'easeinout',
                    ])
                    ->series(array([
                        'barPercentage' => 0.5,
                        'label' => __('Consumption'),
                        'borderColor' => '#f7a35c',
                        'data' => collect($this->months())->map(function($month) use ($resource) {
                            return $resource->percapitas->flatMap->reports->filter(function($report) use ($month) { 
                                return $report->target_date->format($this->dateFormat()) === $month;
                            })->sum('value');
                        })->all(),
                    ],[
                        'barPercentage' => 0.5,
                        'label' => __('Balance'),
                        'borderColor' => '#90ed7d',
                        'data' => collect($this->months())->map(function($month) use ($resource) {
                            return $resource->percapitas->flatMap->reports->filter(function($report) use ($month) { 
                                return $report->target_date->format($this->dateFormat()) === $month;
                            })->sum('balance');
                        })->all(),
                    ]))
                    ->options([
                        'xaxis' => [
                            'categories' => $this->months()
                        ], 
                    ])
                    ->width('full')
                    ->withMeta([
                        'uriKey' => $resource->name
                    ]),

                (new LineChart())
                    ->title((new MeasurableResource($resource))->title().PHP_EOL.__('Aggregate'))
                    ->animations([
                        'enabled' => true,
                        'easing' => 'easeinout',
                    ])
                    ->series(array([
                        'barPercentage' => 0.5,
                        'label' => __('Consumption'),
                        'borderColor' => '#f7a35c',
                        'data' => collect($this->months())->map(function($month, $key) use ($resource) {
                            return $resource->percapitas->flatMap->reports->filter(function($report) use ($key) {
                                return $report->target_date->lte(now()->addMonths($key));
                            })->sum('value');
                        })->all(),
                    ],[
                        'barPercentage' => 0.5,
                        'label' => __('Balance'),
                        'borderColor' => '#90ed7d',
                        'data' => collect($this->months())->map(function($month, $key) use ($resource) {
                            return $resource->percapitas->flatMap->reports->filter(function($report) use ($month) { 
                                return $report->target_date->format($this->dateFormat()) === $month;
                            })->sum('balance') * ($key + 1);
                        })->all(),
                    ]))
                    ->options([
                        'xaxis' => [
                            'categories' => $this->months()
                        ],
                    ])
                    ->width('full')
                    ->withMeta([
                        'uriKey' => $resource->name.'-Aggregated'
                    ]),
            ];
        }); 
    }

    /**
     * Returns an array of the year months.
     * 
     * @return array
     */
    public function months()
    {
        return [
            now()->format($this->dateFormat()),
            now()->addMonths(1)->format($this->dateFormat()),
            now()->addMonths(2)->format($this->dateFormat()),
            now()->addMonths(3)->format($this->dateFormat()),
            now()->addMonths(4)->format($this->dateFormat()),
            now()->addMonths(5)->format($this->dateFormat()),
            now()->addMonths(6)->format($this->dateFormat()),
            now()->addMonths(7)->format($this->dateFormat()),
            now()->addMonths(8)->format($this->dateFormat()),
            now()->addMonths(9)->format($this->dateFormat()),
            now()->addMonths(10)->format($this->dateFormat()),
            now()->addMonths(11)->format($this->dateFormat()),
        ];
    }

    /**
     * Returns the month format string.
     * 
     * @return string
     */
    public function dateFormat()
    {
        return 'M';
    }

    /**
     * Get the URI key for the dashboard.
     *
     * @return string
     */
    public static function uriKey()
    {
        return 'shaghool-consumption-reports';
    }
}