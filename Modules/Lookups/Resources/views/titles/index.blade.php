@extends('layouts.master')

@section('title', __('Titles List'))

@section('content')

 <div class="pagetitle">
      <h1>{{ __('Titles List') }}</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">{{ __('Settings') }}</li>
          <li class="breadcrumb-item active">{{ __('Titles') }}</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <div class="card d-inline-block">
        <div class="card-body p-20">
            <a href="{{ route('titles.create') }}" class="btn btn-success">{{ __('Add New Title') }}</a>
        </div>
    </div>

    <div class="col-lg-12">
        <div class="card">
            <div class="card-body p-20">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th scope="col" class="col-1">{{ __('#') }}</th>
                    <th scope="col" class="col-9">{{ __('Name') }}</th>
                    <th scope="col" class="col-2">{{ __('Actions') }}</th>
                </tr>
                </thead>
                <tbody>
                  @forelse($titles as $title)
                        <tr>
                            <th scope="row">{{ $loop->iteration }}</th>
                            <td class="text-start">{{ $title->name }}</td>
                            <td>
                                <a href="{{ route('titles.edit', $title->id) }}" class="btn btn-primary btn-sm me-1">{{ __('Edit') }}</a>

                                @include('lookups::components.delete-button', [
                                    'action' => route('titles.destroy', $title->id),
                                    'confirm' => __('Are you sure to delete this title?'),
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">{{ __('No titles found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
              </table>
            </div>
        </div>
    </div>
@endsection
