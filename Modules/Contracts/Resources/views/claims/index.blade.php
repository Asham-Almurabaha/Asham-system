@extends('layouts.master')

@section('title', __('contracts::claims.claims_list'))

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">{{ __('contracts::claims.claims_list') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('contracts.index') }}">{{ __('contracts::contracts.Contracts') }}</a></li>
            <li class="breadcrumb-item active">{{ __('contracts::claims.claims') }}</li>
        </ol>
    </nav>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-center p-2">
        <a href="{{ route('contract-claims.create') }}" class="btn btn-success">
            <i class="bi bi-plus-lg"></i> {{ __('contracts::claims.add_claim') }}
        </a>

        <span class="ms-auto small text-muted">
            {{ __('contracts::claims.results_count', ['count' => number_format($claims->total())]) }}
        </span>

        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse"
                data-bs-target="#claimsFilterBar" aria-expanded="{{ request()->hasAny(['contract_number', 'filed_in_party', 'filed_against_party']) ? 'true' : 'false' }}"
                aria-controls="claimsFilterBar">
            {{ __('contracts::claims.filters') }}
        </button>
    </div>

    <div class="collapse @if(request()->hasAny(['contract_number', 'filed_in_party', 'filed_against_party'])) show @endif border-top" id="claimsFilterBar">
        <div class="card-body">
            <form action="{{ route('contract-claims.index') }}" method="GET" class="row gy-2 gx-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label mb-1">{{ __('contracts::claims.contract_number') }}</label>
                    <input type="text" name="contract_number" value="{{ request('contract_number') }}" class="form-control form-control-sm" placeholder="{{ __('contracts::claims.contract_number_placeholder') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label mb-1">{{ __('contracts::claims.filed_in_party') }}</label>
                    <input type="text" name="filed_in_party" value="{{ request('filed_in_party') }}" class="form-control form-control-sm" placeholder="{{ __('contracts::claims.filed_in_party_placeholder') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label mb-1">{{ __('contracts::claims.filed_against_party') }}</label>
                    <input type="text" name="filed_against_party" value="{{ request('filed_against_party') }}" class="form-control form-control-sm" placeholder="{{ __('contracts::claims.filed_against_party_placeholder') }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('contracts::claims.search') }}</button>
                </div>
                <div class="col-auto">
                    <a href="{{ route('contract-claims.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('contracts::claims.clear') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px">#</th>
                        <th>{{ __('contracts::claims.contract_number') }}</th>
                        <th>{{ __('contracts::claims.filed_in_party') }}</th>
                        <th>{{ __('contracts::claims.claim_amount') }}</th>
                        <th>{{ __('contracts::claims.claim_date') }}</th>
                        <th>{{ __('contracts::claims.document_number') }}</th>
                        <th>{{ __('contracts::claims.filed_against_party') }}</th>
                        <th style="width:120px;">{{ __('contracts::claims.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($claims as $claim)
                    <tr>
                        <td class="text-muted">{{ $loop->iteration + ($claims->currentPage() - 1) * $claims->perPage() }}</td>
                        <td class="text-start">{{ $claim->contract->contract_number ?? ('#' . $claim->contract_id) }}</td>
                        <td>{{ $claim->filed_in_party }}</td>
                        <td>{{ number_format((float) $claim->claim_amount, 2) }}</td>
                        <td>{{ optional($claim->claim_date)->format('Y-m-d') }}</td>
                        <td>{{ $claim->document_number }}</td>
                        <td>{{ $claim->filed_against_party }}</td>
                        <td>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="{{ route('contract-claims.edit', $claim) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <form action="{{ route('contract-claims.destroy', $claim) }}" method="POST" onsubmit="return confirm('{{ __('contracts::claims.delete_confirm') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-4">
                            <div class="text-muted">{{ __('contracts::claims.no_results') }}</div>
                            <div class="mt-2">
                                <a href="{{ route('contract-claims.create') }}" class="btn btn-sm btn-success">
                                    <i class="bi bi-plus-lg"></i> {{ __('contracts::claims.add_first_claim') }}
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($claims->hasPages())
        <div class="card-footer bg-white">
            {{ $claims->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
