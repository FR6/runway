<?php

namespace DoubleThreeDigital\Runway\Http\Controllers;

use DoubleThreeDigital\Runway\Support\ModelFinder;
use Illuminate\Http\Request;
use Statamic\Http\Controllers\CP\CpController;

class ModelListingButtonController extends CpController
{
    public function index(Request $request, $model)
    {
        $model = ModelFinder::find($model);
        $listingButton = $model['listing_buttons'][$request->get('listing-button')];

        if (is_callable($listingButton)) {
            return $listingButton($request, $model);
        }

        return (new $listingButton())->__invoke($request, $model);
    }
}
