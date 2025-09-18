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


<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px">#</th>
                        <th>{{ __('contracts::claims.contract_number') }}</th>
                        <th>{{ __('contracts::claims.claim_first_party') }}</th>
                        <th>{{ __('contracts::claims.filed_party_role') }}</th>
                        <th>{{ __('contracts::claims.claim_amount') }}</th>
                        <th>{{ __('contracts::claims.claim_date') }}</th>
                        <th>{{ __('contracts::claims.document_number') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($claims as $claim)
                    <tr>
                        <td class="text-muted">{{ $loop->iteration + ($claims->currentPage() - 1) * $claims->perPage() }}</td>
                        <td class="text-start">{{ $claim->contract->contract_number ?? ('#' . $claim->contract_id) }}</td>
                        <td class="text-start">{{ optional($claim->claimFirstParty)->name ?? '—' }}</td>
                        <td class="text-start">
                            <div>{{ $claim->filed_party_name ?? '—' }}</div>
                            @if ($claim->filed_party_role)
                                <div class="text-muted small">{{ __('contracts::claims.party_role_' . $claim->filed_party_role) }}</div>
                            @endif
                        </td>
                        <td>{{ number_format((float) $claim->claim_amount, 2) }}</td>
                        <td>{{ optional($claim->claim_date)->format('Y-m-d') }}</td>
                        <td>{{ $claim->document_number }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-4">
                            <div class="text-muted">{{ __('contracts::claims.no_results') }}</div>
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
