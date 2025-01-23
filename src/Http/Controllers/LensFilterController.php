<?php

namespace Okol\DependentFilter\Http\Controllers;

use Laravel\Nova\Http\Requests\LensRequest;

class LensFilterController
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function options(LensRequest $request)
    {
        $filter = $request->lens()->availableFilters($request)->first(function ($filter) use ($request) {
            return $filter->key() === $request->filter;
        });

        if (! $filter) {
            abort(404);
        }

        return response()->json(
            $filter->getOptions($request, json_decode(base64_decode($request->query('filters')), true))
        );
    }
}
