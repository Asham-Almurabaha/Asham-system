@extends('layouts.master')

@section('title', __('Product Entries List'))

@section('content')

<div class="pagetitle">
    <h1>{{ __('Product Entries List') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">{{ __('Settings') }}</li>
            <li class="breadcrumb-item active">{{ __('Product Entries') }}</li>
        </ol>
    </nav>
</div>

<div class="card d-inline-block mb-3">
    <div class="card-body p-20">
        <a href="{{ route('product_entries.create') }}" class="btn btn-success">{{ __('Add New Product Entry') }}</a>
    </div>
</div>

<div class="col-lg-12">
    <div class="card">
        <div class="card-body p-20">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col" class="col-1">{{ __('#') }}</th>
                        <th>{{ __('Product') }}</th>
                        <th>{{ __('Quantity') }}</th>
                        <th>{{ __('Purchase Price') }}</th>
                        <th>{{ __('Entry Date') }}</th>
                        <th scope="col" class="col-2">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $entry->product->name }}</td>
                        <td>{{ $entry->quantity }}</td>
                        <td>{{ number_format($entry->purchase_price, 2) }}</td>
                        <td>{{ \Carbon\Carbon::parse($entry->entry_date)->format('Y-m-d') }}</td>
                        <td>
                            <a href="{{ route('product_entries.edit', $entry->id) }}" class="btn btn-primary btn-sm me-1">{{ __('Edit') }}</a>
                            <form action="{{ route('product_entries.destroy', $entry->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure to delete this entry?') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">{{ __('Delete') }}</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">{{ __('No entries found.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
