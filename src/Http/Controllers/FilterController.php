<?php

namespace Fbtc\DependentFilter\Http\Controllers;

use Laravel\Nova\Http\Requests\NovaRequest;

class FilterController
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function options(NovaRequest $request)
    {
        $filter = $request->newResource()->availableFilters($request)->first(function ($filter) use ($request) {
            return $filter->key() === $request->query('filter');
        });

        if (! $filter) {
            abort(404);
        }

        return response()->json(
            $filter->getOptions($request, json_decode(base64_decode($request->query('filters')), true))
        );
    }
}
