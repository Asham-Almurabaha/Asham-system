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
                        <x-add-button href="{{ route('product_entries.create') }}" variant="success" class="ms-sm-auto">
                            {{ __('Add New Product Entry') }}
                        </x-add-button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <x-table striped>
                        <x-slot name="head">
                            <tr>
                                <th scope="col" class="text-nowrap">{{ __('#') }}</th>
                                <th scope="col">{{ __('Product') }}</th>
                                <th scope="col" class="text-nowrap">{{ __('Quantity') }}</th>
                                <th scope="col" class="text-nowrap">{{ __('Purchase Price') }}</th>
                                <th scope="col" class="text-nowrap">{{ __('Entry Date') }}</th>
                                <th scope="col" class="text-end text-nowrap">{{ __('Actions') }}</th>
                            </tr>
                        </x-slot>

                        @forelse($entries as $entry)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $entry->product->name }}</td>
                                <td>{{ $entry->quantity }}</td>
                                <td>{{ number_format($entry->purchase_price, 2) }}</td>
                                <td>{{ \Carbon\Carbon::parse($entry->entry_date)->format('Y-m-d') }}</td>
                                <td class="text-end">
                                    <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-end">
                                        <x-edit-button href="{{ route('product_entries.edit', $entry->id) }}" size="sm">
                                            {{ __('Edit') }}
                                        </x-edit-button>
                                        <form action="{{ route('product_entries.destroy', $entry->id) }}" method="POST" class="m-0"
                                              onsubmit="return confirm('{{ __('Are you sure to delete this entry?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <x-delete-button type="submit" size="sm">
                                                {{ __('Delete') }}
                                            </x-delete-button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">{{ __('No entries found.') }}</td>
                            </tr>
                        @endforelse
                    </x-table>
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
