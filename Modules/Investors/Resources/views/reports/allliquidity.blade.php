@extends('layouts.print-landscape')

@section('title', __('reports.Current Investors Liquidity Report'))
@section('report_title', __('reports.Current Investors Liquidity Report'))

@php
  $cs = $currencySymbol ?? 'ر.س';

  $isPaginated = $rows instanceof \Illuminate\Pagination\LengthAwarePaginator;
  $items = $isPaginated ? $rows->items() : (is_iterable($rows) ? $rows : []);
  $items = collect($items);

  $countAll     = $isPaginated ? $rows->total() : $items->count();
  $pageCount    = $items->count();
  $pageSum      = (float) $items->sum('liquidity');
  $avgLiquidity = $countAll > 0 ? (($grandTotal ?? 0) / $countAll) : 0;

  $posCount     = (int) $items->filter(fn($r)=>(float)$r->liquidity > 0)->count();
  $negCount     = (int) $items->filter(fn($r)=>(float)$r->liquidity < 0)->count();
  $zeroCount    = max(0, $pageCount - $posCount - $negCount);

  $q        = data_get($filters ?? [], 'q', '');
  $perPage  = (int) data_get($filters ?? [], 'per_page', 25);
@endphp

@push('styles')
  <style>
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
  </style>
@endpush

@section('content')
  <div class="small-muted mb-2">
    @lang('reports.Based on ledger: in minus out per investor.')
  </div>

  <div class="toolbar soft p-3 mb-3 no-print">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-12 col-md-6">
        <label class="form-label mb-1 small">@lang('reports.Search by name')</label>
        <input type="text" name="q" class="form-control" value="{{ e($q) }}" placeholder="@lang('investors::investors.Type investor name...')">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label mb-1 small">@lang('reports.Per Page')</label>
        <select name="per_page" class="form-select">
          @foreach([10,25,50,100] as $n)
            <option value="{{ $n }}" @selected($perPage==$n)>{{ $n }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-6 col-md-4 d-flex gap-2">
        <x-button.action type="submit" variant="primary" class="flex-fill"><i class="bi bi-search"></i> {{ __('Search') }}</x-button.action>
        <x-button.action href="{{ url()->current() }}" variant="secondary" :outline="true" class="flex-fill">{{ __('Clear') }}</x-button.action>
      </div>
    </form>
  </div>

  <div class="row g-3 kpi mb-4">
    <div class="col-12 col-md-3">
      <div class="card"><div class="card-body text-center">
        <div class="small-muted">@lang('reports.Total Investors (All)')</div>
        <div class="fs-5 fw-bold">{{ number_format($countAll) }}</div>
      </div></div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card"><div class="card-body text-center">
        <div class="small-muted">@lang('reports.Total Liquidity (All)')</div>
        <div class="fs-5 fw-bold {{ ($grandTotal??0)>=0 ? 'text-success' : 'text-danger' }}">
          {{ number_format((float)($grandTotal ?? 0), 2) }} <span class="small-muted">{{ $cs }}</span>
        </div>
      </div></div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card"><div class="card-body text-center">
        <div class="small-muted">@lang('reports.Average Liquidity per Investor')</div>
        <div class="fs-5 fw-bold">{{ number_format($avgLiquidity, 2) }} <span class="small-muted">{{ $cs }}</span></div>
      </div></div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card"><div class="card-body text-center">
        <div class="small-muted">@lang('reports.Page breakdown: positive/zero/negative')</div>
        <div class="fs-5 fw-bold">
          {{ $posCount }} / {{ $zeroCount }} / {{ $negCount }}
        </div>
      </div></div>
    </div>
  </div>

  <x-table head-class="table-light" striped bordered class="text-center" :hover="false">
      <x-slot name="head">
          <tr>
            <th style="width:56px">#</th>
            <th class="text-start">@lang('app.Investor')</th>
            <th>@lang('reports.Contracts (Active/Total)')</th>
            <th>@lang('reports.Initial Capital')</th>
            <th>@lang('reports.Current Liquidity')</th>
            <th class="no-print" style="width:120px">{{ __('Actions') }}</th>
          </tr>
      </x-slot>
      @if($isPaginated)
      
            @endif
      
              @forelse($items as $i => $r)
                @php
                  $liq  = (float) ($r->liquidity ?? 0);
                  $init = (float) ($r->initial_capital ?? 0);
                  $act  = (int)   ($r->contracts_active ?? 0);
                  $tot  = (int)   ($r->contracts_total  ?? 0);
                @endphp
                <tr>
                  <td>{{ $isPaginated ? ($rows->firstItem() + $i) : ($i + 1) }}</td>
                  <td class="text-start">
                    <div class="fw-semibold">
                      @if(Route::has('investors.show'))
                        <a href="{{ route('investors.show', $r->id) }}">{{ $r->name }}</a>
                      @else
                        {{ $r->name }}
                      @endif
                    </div>
                  </td>
                  <td class="fw-semibold">{{ $act }} / {{ $tot }}</td>
                  <td class="text-primary fw-semibold">
                    {{ number_format($init, 2) }} <span class="small-muted">{{ $cs }}</span>
                  </td>
                  <td class="fw-bold {{ $liq>=0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($liq, 2) }} <span class="small-muted">{{ $cs }}</span>
                  </td>
                  <td class="no-print">
                    @if(Route::has('investors.show'))
                      <x-button.action href="{{ route('investors.show', $r->id) }}" variant="primary" :outline="true" size="sm">@lang('pages.Details')</x-button.action>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="py-5 text-muted">@lang('reports.No matching data.')</td>
                </tr>
              @endforelse
      <x-slot name="footer">
          <tr>
            <th colspan="6" class="bg-white">
              <div class="no-print d-flex justify-content-center p-2">
                {{ $rows->withQueryString()->links('pagination::bootstrap-5') }}
              </div>
            </th>
          </tr>
      </x-slot>
  </x-table>
@endsection

@section('actions')
  <x-button.action href="{{ route('investors.index') }}" variant="secondary" :outline="true">↩ @lang('app.Back')</x-button.action>
  <x-button.refresh :href="url()->current()" />
@endsection

