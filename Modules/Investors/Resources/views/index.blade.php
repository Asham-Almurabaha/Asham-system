@extends('layouts.master')

@section('title', __('investors::investors.Investors List'))

@section('content')

<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">{{ __('investors::investors.Investors List') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">{{ __('investors::investors.Investors') }}</li>
        </ol>
    </nav>
</div>

{{-- ====== شريط الأدوات + فلاتر ====== --}}
<div class="card shadow-sm mb-3">
  <div class="card-body d-flex flex-wrap gap-2 align-items-center p-2">

    <div class="btn-group" role="group" aria-label="Investor Actions">
      <x-button.action href="{{ route('investors.create') }}" variant="success">
        <i class="bi bi-plus-lg"></i> {{ __('investors::investors.Add Investor') }}
      </x-button.action>
      <x-button.action href="{{ route('investors.dashboard') }}" variant="dark" :outline="true">
        <i class="bi bi-speedometer2"></i> {{ __('investors::investors.View Dashboard') }}
      </x-button.action>
      @if (session('failures') && count(session('failures')))
        <x-button.action href="{{ route('investors.import.export_failures') }}" variant="warning">
          <i class="bi bi-exclamation-triangle"></i> {{ __('investors::investors.Export Failures') }}
        </x-button.action>
      @endif
    </div>

    <span class="ms-auto small text-muted">
      {{ __('investors::investors.Results') }}: <strong>{{ $investors->total() }}</strong>
    </span>

    <x-button.action type="button" variant="secondary" :outline="true" size="sm"
              data-bs-toggle="collapse" data-bs-target="#filterBar"
              aria-expanded="false" aria-controls="filterBar">
      {{ __('investors::investors.Filter') }}
    </x-button.action>
  </div>

  <div class="collapse @if(request()->has('investor_q')) show @endif border-top" id="filterBar">
    <div class="card-body">
      <form id="filterForm" action="{{ route('investors.index') }}" method="GET" class="row gy-2 gx-2 align-items-end">
        <div class="col-12 col-md-4">
          <label class="form-label mb-1">{{ __('investors::investors.Investor (by name)') }}</label>
          <input type="text" name="investor_q" value="{{ request('investor_q') }}"
                 class="form-control form-control-sm auto-submit-input" placeholder="{{ __('investors::investors.Type investor name...') }}">
        </div>

        <div class="col-12 col-md-1">
          <x-button.action href="{{ route('investors.index') }}" variant="secondary" :outline="true" size="sm" class="w-100">{{ __('investors::investors.Clear') }}</x-button.action>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- ====== الجدول ====== --}}
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div>
            <x-table head-class="table-light position-sticky top-0" class="text-center">
                <x-slot name="head">
                    <tr>
                        <th style="width:60px">#</th>
                        <th>{{ __('investors::investors.Name') }}</th>
                        <th>{{ __('investors::investors.Investment Start Date') }}</th>
                        <th>{{ __('Current Liquidity') }}</th>
                        <th>{{ __('investors::investors.Active Contracts') }}</th>
                        <th>{{ __('Remaining In Active') }}</th>
                    
                    </tr>
                </x-slot>
                @forelse ($investors as $investor)
                    <tr>
                        <td class="text-muted">{{ $loop->iteration + ($investors->currentPage() - 1) * $investors->perPage() }}</td>
                        <td class="text-start">
                            <a href="{{ route('investors.show', $investor) }}" class="text-decoration-none fw-bold text-dark hover-primary">
                                {{ $investor->name }}
                            </a>
                        </td>
                        <td>
                            @if($investor->investment_start_date)
                                <span dir="ltr">{{ $investor->investment_start_date->format('Y-m-d') }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ number_format((float)($liquidityByInvestor[$investor->id] ?? 0), 2) }}</td>
                        <td>{{ number_format((int)($activeCountByInvestor[$investor->id] ?? 0)) }}</td>
                        <td>{{ number_format((float)($remainingByInvestor[$investor->id] ?? 0), 2) }}</td>
                
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-5">
                            <div class="text-muted">{{ __('investors::investors.No matching results.') }} <a href="{{ route('investors.index') }}" class="ms-1">{{ __('investors::investors.Show All') }}</a></div>
                            <div class="mt-3"><x-button.action href="{{ route('investors.create') }}" variant="success" size="sm">+ {{ __('investors::investors.Add First Investor') }}</x-button.action></div>
                        </td>
                    </tr>
                @endforelse
            </x-table>
        </div>
    </div>

    @if($investors->hasPages())
    <div class="card-footer bg-white">
        {{ $investors->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // tooltips
    const tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el, {container: 'body'}));

    // auto-submit inputs with debounce (اسم فقط)
    let typingTimer;
    document.querySelectorAll('.auto-submit-input').forEach(el => {
        el.addEventListener('input', function () {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                document.getElementById('filterForm').submit();
            }, 700);
        });
    });
});
</script>
@endpush
