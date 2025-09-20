@extends('layouts.master')

@section('title', __('app.Add Contract Status'))

@section('content')

    <div class="pagetitle">
      <h1>@lang('app.Add Contract Status')</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">@lang('app.Settings (Breadcrumb)')</li>
          <li class="breadcrumb-item">@lang('app.Contract Statuses')</li>
          <li class="breadcrumb-item active">@lang('app.Add Contract Status')</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->
<div class="col-lg-6">
        <div class="card">
            <div class="card-body p-20">
              <form action="{{ route('contract_statuses.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">@lang('app.Status Name')</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required autofocus>
                    </div>

                    <x-button type="submit" variant="success" :outline="true">@lang('app.Save')</x-button>
                    <x-button href="{{ route('contract_statuses.index') }}" variant="secondary" :outline="true">@lang('app.Cancel')</x-button>
                </form>
            </div>
        </div>
    </div>

@endsection
