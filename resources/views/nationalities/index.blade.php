@extends('layouts.master')

@section('title', __('Nationalities List'))

@section('content')

    <div class="pagetitle">
        <h1>{{ __('Nationalities List') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">{{ __('Settings') }}</li>
                <li class="breadcrumb-item active">{{ __('Nationalities') }}</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->

    <div class="card d-inline-block">
        <div class="card-body p-20">
            <a href="{{ route('nationalities.create') }}" class="btn btn-success">{{ __('Add New Nationality') }}</a>
        </div>
    </div>

    <div class="col-lg-12 mt-3">
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
                        @forelse($nationalities as $nationality)
                            <tr>
                                <th scope="row">{{ $loop->iteration }}</th>
                                <td class="text-start">{{ $nationality->name }}</td>
                                <td>
                                    <a href="{{ route('nationalities.edit', $nationality->id) }}" class="btn btn-primary btn-sm me-1">{{ __('Edit') }}</a>

                                    <form action="{{ route('nationalities.destroy', $nationality->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure to delete this nationality?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center">{{ __('No nationalities found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
