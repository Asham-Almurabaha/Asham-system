@extends('layouts.master')

@section('title', __('Product Entries List'))

@section('content')
<div class="container-fluid py-3">
    <div class="pagetitle mb-3">
        <h1>{{ __('Product Entries List') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">{{ __('Settings') }}</li>
                <li class="breadcrumb-item active">{{ __('Product Entries') }}</li>
            </ol>
        </nav>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 pb-0">
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2 justify-content-between">
                        <h2 class="h5 mb-0">{{ __('Product Entries List') }}</h2>
                        <a href="{{ route('product_entries.create') }}" class="btn btn-success ms-sm-auto">
                            {{ __('Add New Product Entry') }}
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" class="text-nowrap">{{ __('#') }}</th>
                                    <th scope="col">{{ __('Product') }}</th>
                                    <th scope="col" class="text-nowrap">{{ __('Quantity') }}</th>
                                    <th scope="col" class="text-nowrap">{{ __('Purchase Price') }}</th>
                                    <th scope="col" class="text-nowrap">{{ __('Entry Date') }}</th>
                                    <th scope="col" class="text-end text-nowrap">{{ __('Actions') }}</th>
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
                                        <td class="text-end">
                                            <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-end">
                                                <a href="{{ route('product_entries.edit', $entry->id) }}" class="btn btn-primary btn-sm">
                                                    {{ __('Edit') }}
                                                </a>
                                                <form action="{{ route('product_entries.destroy', $entry->id) }}" method="POST" class="m-0"
                                                      onsubmit="return confirm('{{ __('Are you sure to delete this entry?') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        {{ __('Delete') }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">{{ __('No entries found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if(method_exists($entries, 'hasPages') && $entries->hasPages())
                    <div class="card-footer bg-white border-0">
                        {{ $entries->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
