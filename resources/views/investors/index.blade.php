@extends('layouts.master')

@section('title', __('Investors List'))

@section('content')

<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">{{ __('Investors List') }}</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">{{ __('Investors') }}</li>
        </ol>
    </nav>
</div>

@php
    $allTotal    = (int)($investorsTotalAll ?? 0);
    $allActive   = (int)($activeInvestorsTotalAll ?? 0);
    $allInactive = max($allTotal - $allActive, 0);

    $activePct   = $allTotal > 0 ? round(($allActive / $allTotal) * 100, 1) : 0;
    $inactivePct = $allTotal > 0 ? round(($allInactive / $allTotal) * 100, 1) : 0;

    $newThisMonthAll = (int)($newInvestorsThisMonthAll ?? 0);
    $newThisWeekAll  = (int)($newInvestorsThisWeekAll  ?? 0);
@endphp

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">



{{-- ====== كروت عامة ====== --}}
<div class="row g-4 mb-3" dir="rtl">
    <div class="col-12 col-md-3">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-people fs-4 text-primary"></i></div>
                <div class="flex-grow-1">
                    <div class="subnote">{{ __('Total Investors — All System') }}</div>
                    <div class="kpi-value fw-bold">{{ number_format($allTotal) }}</div>
                    <div class="subnote">{{ __('Not affected by filters') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-3">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-person-check fs-4 text-success"></i></div>
                <div class="flex-grow-1">
                    <div class="subnote">{{ __('Active Investors') }}</div>
                    <div class="kpi-value fw-bold">{{ number_format($allActive) }}</div>
                    <div class="subnote">{{ __('Active Percentage') }}: {{ number_format($activePct,1) }}%</div>
                </div>
            </div>
            <div class="mt-3">
                <div class="progress bar-8">
                    <div class="progress-bar" style="width: {{ $activePct }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-3">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-person-x fs-4 text-danger"></i></div>
                <div class="flex-grow-1">
                    <div class="subnote">{{ __('Inactive Investors') }}</div>
                    <div class="kpi-value fw-bold">{{ number_format($allInactive) }}</div>
                    <div class="subnote">{{ __('Percentage') }}: {{ number_format($inactivePct,1) }}%</div>
                </div>
            </div>
            <div class="mt-3">
                <div class="progress bar-8">
                    <div class="progress-bar bg-danger" style="width: {{ $inactivePct }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-3">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-calendar2-plus fs-4 text-primary"></i></div>
                <div class="flex-grow-1">
                    <div class="subnote">{{ __('New Investors This Month') }}</div>
                    <div class="kpi-value fw-bold">{{ number_format($newThisMonthAll) }}</div>
                    <div class="subnote">{{ __('This Week') }}: {{ number_format($newThisWeekAll) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ====== شريط الأدوات + فلاتر ====== --}}
<div class="card shadow-sm mb-3">
  <div class="card-body d-flex flex-wrap gap-2 align-items-center p-2">

    <div class="btn-group" role="group" aria-label="Investor Actions">
      <a href="{{ route('investors.create') }}" class="btn btn-success">
        <i class="bi bi-plus-lg"></i> {{ __('Add Investor') }}
      </a>
      @role('admin')
        <a href="{{ route('investors.import.form') }}" class="btn btn-outline-primary">
            <i class="bi bi-upload"></i> {{ __('Import Excel') }}
        </a>
      @endrole

      {{-- تم حذف زر التمبليت زي العملاء --}}
      {{-- @if (Route::has('investors.import.template'))
        <a href="{{ route('investors.import.template') }}" class="btn btn-outline-secondary">
          <i class="bi bi-file-earmark-spreadsheet"></i> تمبليت
        </a>
      @endif --}}

      @if (session('failures') && count(session('failures')))
        <a href="{{ route('investors.import.export_failures') }}" class="btn btn-warning">
          <i class="bi bi-exclamation-triangle"></i> {{ __('Export Failures') }}
        </a>
      @endif
    </div>

    <div class="btn-group">
      <button type="button" class="btn btn-outline-dark dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
        📊 {{ __('Reports') }}
      </button>
      <ul class="dropdown-menu dropdown-menu-end text-end">
        <li>
          <a class="dropdown-item" href="{{ route('reports.investors.Allliquidity') }}">
            📄 {{ __('Investors Liquidity Report') }}
          </a>
        </li>
      </ul>
    </div>

    <span class="ms-auto small text-muted">
      {{ __('Results') }}: <strong>{{ $investors->total() }}</strong>
    </span>

    <button class="btn btn-outline-secondary btn-sm" type="button"
            data-bs-toggle="collapse" data-bs-target="#filterBar"
            aria-expanded="false" aria-controls="filterBar">
      {{ __('Filter') }}
    </button>
  </div>

  <div class="collapse @if(request()->has('investor_q')) show @endif border-top" id="filterBar">
    <div class="card-body">
      <form id="filterForm" action="{{ route('investors.index') }}" method="GET" class="row gy-2 gx-2 align-items-end">
        <div class="col-12 col-md-4">
          <label class="form-label mb-1">{{ __('Investor (by name)') }}</label>
          <input type="text" name="investor_q" value="{{ request('investor_q') }}"
                 class="form-control form-control-sm auto-submit-input" placeholder="{{ __('Type investor name...') }}">
        </div>

        <div class="col-12 col-md-1">
          <a href="{{ route('investors.index') }}" class="btn btn-outline-secondary btn-sm w-100">{{ __('Clear') }}</a>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- ====== الجدول ====== --}}
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div>
            <table class="table table-hover align-middle text-center mb-0">
                <thead class="table-light position-sticky top-0" style="z-index: 1;">
                    <tr>
                        <th style="width:60px">#</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('National ID') }}</th>
                        <th>{{ __('Phone') }}</th>
                        <th>{{ __('Current Liquidity') }}</th>
                        <th>{{ __('Active Contracts') }}</th>
                        <th>{{ __('Remaining In Active') }}</th>
                        
                    </tr>
                </thead>
                <tbody>
                    @forelse ($investors as $investor)
                        <tr>
                            <td class="text-muted">{{ $loop->iteration + ($investors->currentPage() - 1) * $investors->perPage() }}</td>
                            <td class="text-start">
                                <a href="{{ route('investors.show', $investor) }}" class="text-decoration-none fw-bold text-dark hover-primary">
                                    {{ $investor->name }}
                                </a>
                            </td>
                            <td dir="ltr">{{ $investor->national_id ?? '—' }}</td>
                            <td dir="ltr">{{ $investor->phone ?? '—' }}</td>
                            <td>{{ number_format((float)($liquidityByInvestor[$investor->id] ?? 0), 2) }}</td>
                            <td>{{ number_format((int)($activeCountByInvestor[$investor->id] ?? 0)) }}</td>
                            <td>{{ number_format((float)($remainingByInvestor[$investor->id] ?? 0), 2) }}</td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-5">
                                <div class="text-muted">{{ __('No matching results.') }} <a href="{{ route('investors.index') }}" class="ms-1">{{ __('Show All') }}</a></div>
                                <div class="mt-3"><a href="{{ route('investors.create') }}" class="btn btn-sm btn-success">+ {{ __('Add First Investor') }}</a></div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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
