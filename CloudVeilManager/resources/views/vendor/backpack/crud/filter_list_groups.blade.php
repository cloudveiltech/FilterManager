@extends(backpack_view('blank'))

@php
    $defaultBreadcrumbs = [
        trans('backpack::crud.admin') => backpack_url('dashboard'),
        'Filter lists' => backpack_url('filter-list'),
        $filterList->category => false,
    ];
    $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
    <section class="header-operation container-fluid animated fadeIn d-flex mb-2 align-items-baseline d-print-none" bp-section="page-header">
        <h1 class="text-capitalize mb-0" bp-section="page-heading">Group assignments</h1>
        <p class="ms-2 ml-2 mb-0" bp-section="page-subheading">
            {{ $filterList->category }} <span class="text-muted">({{ $filterList->namespace }} / {{ $filterList->type }})</span>
        </p>
        <p class="mb-0 ms-2 ml-2" bp-section="page-subheading-back-button">
            <small>
                <a href="{{ backpack_url('filter-list') }}" class="d-print-none font-sm">
                    <i class="la la-angle-double-{{ config('backpack.base.html_direction') == 'rtl' ? 'right' : 'left' }}"></i>
                    {{ trans('backpack::crud.back_to_all') }} filter lists
                </a>
            </small>
        </p>
    </section>
@endsection

@section('content')
    <div class="row" bp-section="crud-operation-groups">
        <div class="col-md-12">
            @include('crud::inc.grouped_errors')

            <form method="post" action="{{ backpack_url('filter-list/' . $filterList->getKey() . '/groups') }}">
                {!! csrf_field() !!}
                @include('crud::fields.status_assignment_table', [
                    'field' => $field,
                    'crud' => $crud,
                    'entry' => $entry,
                    'action' => 'groups',
                ])

                <div class="form-group mt-3">
                    <a href="{{ backpack_url('filter-list') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save group assignments</button>
                </div>
            </form>
        </div>
    </div>
@endsection
