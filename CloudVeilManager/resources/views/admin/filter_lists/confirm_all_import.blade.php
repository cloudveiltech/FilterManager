@extends(backpack_view('blank'))

@php
    $breadcrumbs = [
        trans('backpack::crud.admin') => backpack_url('dashboard'),
        'Filter lists' => backpack_url('filter-list'),
        'Import all categories' => false,
    ];
@endphp

@section('header')
    <section class="header-operation container-fluid animated fadeIn d-flex mb-2 align-items-baseline d-print-none" bp-section="page-header">
        <h1 class="text-capitalize mb-0" bp-section="page-heading">Import all categories</h1>
    </section>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h2 class="h4">Are you sure you want to import all categories?</h2>
                    <p class="mb-4">This can take some time. Categories with importing disabled will be skipped.</p>

                    <form method="post" action="{{ url('/admin/update') }}">
                        @csrf
                        <input type="hidden" name="confirm_all" value="1">

                        <a href="{{ backpack_url('filter-list') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Import all categories</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
