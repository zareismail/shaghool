<?php

namespace Zareismail\Shaghool\Nova; 

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laravel\Nova\Fields\{ID, Text, Number, Select, DateTime, BelongsTo, MorphTo, HasMany}; 
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
    public static $with = ['percapita', 'auth'];

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
                ->withoutTrashed(),  

            BelongsTo::make(__('Payer'), 'auth', User::class)
                ->withoutTrashed() 
                ->searchable()
                ->debounce(100)
                ->readonly(! is_null($capita = $this->capita ?: $request->findParentModel() ))
                ->default($this->auth_id ?? $capita->auth_id ?? $request->user()->id)
                ->canSee(function($request) {
                    return $request->user()->can('update', static::newModel());
                }), 

            // $this->mergeWhen(! $request->isUpdateOrUpdateAttachedRequest() && $capita, function() use ($capita) {
            //     return [ 
            //         Integer::make(__('Installment Amount'), 'value')
            //             ->required()
            //             ->rules('required')
            //             ->readonly()
            //             ->hideFromIndex()
            //             ->withMeta([
            //                 'value' => $capita->amount
            //             ]), 

            //         Number::make(__('Reports Total'), 'amount')
            //             ->required()
            //             ->rules('required')
            //             ->readonly()
            //             ->withMeta([
            //                 'value' => ($sum = $capita->maturities->where('id', '<=', $this->id ?? $capita->maturities->max('id'))->sum('amount'))
            //             ]),  

            //         Number::make(__('Current installment'), 'installment')
            //             ->required()
            //             ->rules('required')
            //             ->readonly()
            //             ->onlyOnForms()
            //             ->hideWhenUpdating()
            //             ->withMeta([
            //                 'value' => $capita->maturities->count() + ($this->exists ? 0:1)
            //             ]), 

            //         Number::make(__('Debt until here'), 'amount')
            //             ->required()
            //             ->rules('required')
            //             ->readonly()
            //             ->withMeta([
            //                 'value' => ($capita->installments * $capita->amount) - $sum
            //             ]),  

            //         Number::make(__('Lacks'), 'amount')
            //             ->required()
            //             ->rules('required')
            //             ->readonly()
            //             ->withMeta([
            //                 'value' => $sum - ($capita->maturities->where('id', '<=', $this->id ?? $capita->maturities->max('id'))->count() * $capita->amount)
            //             ]),  
            //     ];
            // }),

            Number::make(__('Consumption Value'), 'value')
                ->required()
                ->rules('required'), 

            // DateTime::make(__('Report Date'), 'report_date')
            //     ->required()
            //     ->rules('required'), 
    	];
    }
    /**
     * Determine if this resource is available for navigation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public static function availableForNavigation(Request $request)
    {
        return false;
    }
}