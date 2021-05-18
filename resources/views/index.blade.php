@extends('statamic::layout')
@section('title', $title)
@section('wrapper_class', 'max-w-full')

@section('content')
    <div class="flex items-center justify-between mb-3">
        <h1 class="flex-1">{{ $title }}</h1>

        <a class="btn-primary" href="{{ cp_route('runway.create', ['model' => $model['_handle']]) }}">Create {{ $model['singular'] }}</a>
    </div>

    @if ($records->count())
        <div class="card p-0">
            <div class="flex items-center w-full p-2">
                <form class="w-full flex" action="#" method="get">
                    <input
                        class="input-text flex-1"
                        type="search"
                        name="query"
                        style="height: auto;"
                        placeholder="Search..."
                        value="{{ request()->input('query') }}"
                    >
                </form>

                <div class="ml-2">
                    <form class="w-full flex" action="#" method="get">
                        <select name="contest_id" onchange="this.form.submit()">
                            <option value="0" {{ $contest_id ? '' : 'selected' }}>Tous les concours</option>
                            @foreach($contests as $contest)
                                <option value="{{ $contest->id() }}" {{ $contest->id() == $contest_id ? 'selected' : '' }}>
                                    {{ $contest->get('title') }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>  

                @if(count($model['listing_buttons']) >= 1)
                    <div class="flex items-center ml-2">
                        @foreach($model['listing_buttons'] as $listingButton => $action)
                            <form
                                action="{{ cp_route('runway.listing-buttons', [
                                    'model' => $model['_handle'],
                                ]) }}"
                                method="POST"
                            >
                                @csrf
                                <input type="hidden" name="listing-button" value="{{ $listingButton }}">
                                <input type="hidden" name="contest_id" value="{{ $contest_id }}">
                                <button class="btn">{{ $listingButton }}</button>
                            </form>
                        @endforeach
                    </div>
                @endif
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        @foreach ($columns as $column)
                            <th>{{ $column['title'] }}</th>
                        @endforeach
                        <th class="actions-column"></th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($records as $record)
                        <tr>
                            @foreach ($columns as $column)
                                <td>
                                    @if($column['has_link'])
                                        <div class="flex items-center">
                                            <a href="{{ cp_route('runway.edit', [
                                                'model' => $model['_handle'],
                                                'record' => $record->{$model['route_key']}
                                            ]) }}">{{ $record->{$column['handle']} }}</a>
                                        </div>
                                    @else
                                        {{ $record->{$column['handle']} }}
                                    @endif
                                </td>
                            @endforeach

                            <td class="flex justify-end">
                                <dropdown-list>
                                    <dropdown-item text="Edit" redirect="{{ cp_route('runway.edit', ['model' => $model['_handle'], 'record' => $record->{$model['route_key']}]) }}"></dropdown-item>
                                    <form action="{{ cp_route('runway.destroy', ['model' => $model['_handle'], 'record' => $record->{$model['route_key']}]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')

                                        <dropdown-item class="warning" text="Delete" redirect="#"></dropdown-item>
                                    </form>
                                </dropdown-list>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="my-2">
            {{ $records->links('runway::pagination') }}
        </div>
    @else
        @include('statamic::partials.create-first', [
            'resource' => $title,
            'svg' => 'empty/collection',
            'route' => cp_route('runway.create', ['model' => $model['_handle']]),
        ])
    @endif
@endsection
