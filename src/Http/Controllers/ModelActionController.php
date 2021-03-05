<?php

namespace DoubleThreeDigital\Runway\Http\Controllers;

use DoubleThreeDigital\Runway\Support\ModelFinder;
use Illuminate\Http\Request;
use Statamic\Http\Controllers\CP\ActionController;

class ModelActionController extends ActionController
{
    protected $model;

    /**
     * We're overriding the `run` method with our own, so we can add our
     * route parameters.
     */
    public function runAction(Request $request, $model)
    {
        $this->model = $model;

        parent::run($request);

        return back();
    }

    protected function getSelectedItems($items, $context)
    {
        $model = ModelFinder::find($this->model);

        return $items->map(function ($item) use ($model) {
            return (new $model['model']())->where($model['route_key'], $item)->first();
        });
    }
}
