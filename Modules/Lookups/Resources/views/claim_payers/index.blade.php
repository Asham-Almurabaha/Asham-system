@extends('layouts.master')

@section('title', __('Claim Payers List'))

@section('content')
<div class="pagetitle">
    <h1>{{ __('Claim Payers List') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">{{ __('Settings') }}</li>
            <li class="breadcrumb-item active">{{ __('Claim Payers') }}</li>
        </ol>
    </nav>
</div><!-- End Page Title -->

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card d-inline-block mb-3">
    <div class="card-body p-20">
        <a href="{{ route('claim_payers.create') }}" class="btn btn-success">{{ __('Add New Claim Payer') }}</a>
    </div>
</div>

<div class="col-lg-12">
    <div class="card">
        <div class="card-body p-20">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col" class="col-1">#</th>
                        <th scope="col" class="col-9">{{ __('Name') }}</th>
                        <th scope="col" class="col-2">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($claimPayers as $claimPayer)
                        <tr>
                            <th scope="row">{{ $loop->iteration }}</th>
                            <td class="text-start">{{ $claimPayer->name }}</td>
                            <td>
                                <a href="{{ route('claim_payers.edit', $claimPayer->id) }}" class="btn btn-primary btn-sm me-1">{{ __('Edit') }}</a>

                                @include('lookups::components.delete-button', [
                                    'action' => route('claim_payers.destroy', $claimPayer->id),
                                    'confirm' => __('Are you sure to delete this claim payer?'),
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">{{ __('No claim payers found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
