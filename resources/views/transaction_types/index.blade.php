@extends('layouts.master')

@section('title', __('transaction_types.List of Transaction Types'))

@section('content')

<div class="pagetitle">
    <h1>@lang('transaction_types.List of Transaction Types')</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">@lang('sidebar.Settings')</li>
            <li class="breadcrumb-item active">@lang('sidebar.Transaction Types')</li>
        </ol>
    </nav>
</div><!-- End Page Title -->

<div class="card d-inline-block mb-3">
    <div class="card-body p-20">
        <a href="{{ route('transaction_types.create') }}" class="btn btn-success">@lang('transaction_types.Add New Transaction Type')</a>
    </div>
</div>

<div class="col-lg-12">
    <div class="card">
        <div class="card-body p-20">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col" class="col-1">#</th>
                        <th scope="col" >@lang('transaction_types.Name')</th>
                        <th scope="col" class="col-2">@lang('transaction_types.Actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($types as $type)
                        <tr>
                            <th scope="row">{{ $loop->iteration }}</th>
                            <td class="text-start">{{ $type->name }}</td>
                            <td>
                                <a href="{{ route('transaction_types.edit', $type->id) }}" class="btn btn-primary btn-sm me-1">@lang('transaction_types.Edit')</a>
                                <form action="{{ route('transaction_types.destroy', $type->id) }}" method="POST" class="d-inline" onsubmit="return confirm('@lang('transaction_types.Are you sure you want to delete this transaction type?')');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">@lang('transaction_types.Delete')</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">@lang('transaction_types.No transaction types yet.')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
