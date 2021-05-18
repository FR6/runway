<?php

namespace DoubleThreeDigital\Runway\Http\Controllers;

use DoubleThreeDigital\Runway\Http\Requests\StoreRequest;
use DoubleThreeDigital\Runway\Http\Requests\UpdateRequest;
use DoubleThreeDigital\Runway\Support\ModelFinder;
use Illuminate\Http\Request;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Facades\Entry;

class ModelController extends CpController
{
    public function index(Request $request, $model)
    {
        $model = ModelFinder::find($model);
        $blueprint = $model['blueprint'];

        if (! User::current()->hasPermission("View {$model['_handle']}") && ! User::current()->isSuper()) {
            abort('403');
        }

        $query = (new $model['model']())
            ->orderBy($model['listing_sort']['column'], $model['listing_sort']['direction']);

        if ($searchQuery = $request->input('query')) {
            $query->where(function ($query) use ($searchQuery, $blueprint) {
                $wildcard = '%'.$searchQuery.'%';

                foreach ($blueprint->fields()->items()->toArray() as $field) {
                    $query->orWhere($field['handle'], 'LIKE', $wildcard);
                }
            });
        }

        $columns = collect($model['listing_columns'])
            ->map(function ($columnKey) use ($model, $blueprint) {
                $field = $blueprint->field($columnKey);

                return [
                    'handle' => $columnKey,
                    'title'  => !$field ? $columnKey : $field->display(),
                    'has_link' => $model['listing_columns'][0] === $columnKey,
                ];
            })
            ->toArray();

        // Query filter

        $contest_id = false;
        if($contest_id = $request->input('contest_id')){
            if($contest_id != '0'){
                $query->where('contest_id', $contest_id);
            }
        }

        // Dropdown filter

        $contests = Entry::query()
            ->where('collection', 'contest')
            ->where('locale', 'default')
            ->get();

        return view('runway::index', [
            'title'     => $model['name'],
            'model'     => $model,
            'records'   => $query->paginate(config('statamic.cp.pagination_size')),
            'columns'   => $columns,
            'contests'  => $contests,
            'contest_id' => $contest_id,
        ]);
    }

    public function create(Request $request, $model)
    {
        $model = ModelFinder::find($model);

        if (! User::current()->hasPermission("Create new {$model['_handle']}") && ! User::current()->isSuper()) {
            abort('403');
        }

        $blueprint = $model['blueprint'];
        $fields = $blueprint->fields();
        $fields = $fields->preProcess();

        return view('runway::create', [
            'model'     => $model,
            'blueprint' => $blueprint->toPublishArray(),
            'values'    => $fields->values(),
            'meta'      => $fields->meta(),
            'action'    => cp_route('runway.store', ['model' => $model['_handle']]),
        ]);
    }

    public function store(StoreRequest $request, $model)
    {
        $model = ModelFinder::find($model);
        $record = (new $model['model']());

        if (! User::current()->hasPermission("Create new {$model['_handle']}") && ! User::current()->isSuper()) {
            abort('403');
        }

        foreach ($model['blueprint']->fields()->all() as $fieldKey => $field) {
            if ($field->type() === 'section') {
                continue;
            }

            $processedValue = $field->fieldtype()->process($request->get($fieldKey));

            if (is_array($processedValue)) {
                $processedValue = json_encode($processedValue);
            }

            $record->{$fieldKey} = $processedValue;
        }

        $record->save();

        return [
            'record'    => $record->toArray(),
            'redirect'  => cp_route('runway.edit', [
                'model'     => $model['_handle'],
                'record'    => $record->{$model['primary_key']},
            ]),
        ];
    }

    public function edit(Request $request, $model, $record)
    {
        $model = ModelFinder::find($model);
        $record = (new $model['model']())->where($model['route_key'], $record)->first();

        if (! User::current()->hasPermission("Edit {$model['_handle']}") && ! User::current()->isSuper()) {
            abort('403');
        }

        $values = [];
        $blueprintFieldKeys = $model['blueprint']->fields()->all()->keys()->toArray();

        foreach ($blueprintFieldKeys as $fieldKey) {
            $value = $record->{$fieldKey};

            if ($value instanceof \Carbon\Carbon) {
                $value = $value->format('Y-m-d H:i');
            }

            $values[$fieldKey] = $value;
        }

        $blueprint = $model['blueprint'];
        $fields = $blueprint->fields()->addValues($values)->preProcess();

        return view('runway::edit', [
            'model'     => $model,
            'blueprint' => $blueprint->toPublishArray(),
            'values'    => $fields->values(),
            'meta'      => $fields->meta(),
            'action'    => cp_route('runway.update', [
                'model'     => $model['_handle'],
                'record'    => $record->{$model['primary_key']},
            ]),
        ]);
    }

    public function update(UpdateRequest $request, $model, $record)
    {
        $model = ModelFinder::find($model);
        $record = (new $model['model']())->where($model['route_key'], $record)->first();

        if (! User::current()->hasPermission("Edit {$model['_handle']}") && ! User::current()->isSuper()) {
            abort('403');
        }

        foreach ($model['blueprint']->fields()->all() as $fieldKey => $field) {
            if ($field->type() === 'section') {
                continue;
            }

            $processedValue = $field->fieldtype()->process($request->get($fieldKey));

            if (is_array($processedValue)) {
                $processedValue = json_encode($processedValue);
            }

            $record->{$fieldKey} = $processedValue;
        }

        $record->save();

        return [
            'record'    => $record->toArray(),
        ];
    }

    public function destroy(Request $request, $model, $record)
    {
        $model = ModelFinder::find($model);
        $record = (new $model['model']())->where($model['route_key'], $record)->first();

        if (! User::current()->hasPermission("Delete {$model['_handle']}") && ! User::current()->isSuper()) {
            abort('403');
        }

        $record->delete();

        return redirect(cp_route('runway.index', [
            'model' => $model['_handle'],
        ]))->with('success', "{$model['singular']} deleted");
    }
}
