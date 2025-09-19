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
      <a href="{{ route('investors.create') }}" class="btn btn-success">
        <i class="bi bi-plus-lg"></i> {{ __('investors::investors.Add Investor') }}
      </a>
      <a href="{{ route('investors.dashboard') }}" class="btn btn-outline-dark">
        <i class="bi bi-speedometer2"></i> {{ __('investors::investors.View Dashboard') }}
      </a>
      <div class="btn-group" role="group">
        <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-journal-text"></i> إدارة العمليات المحاسبية
        </button>
        <ul class="dropdown-menu dropdown-menu-end text-end">
          <li>
            <a class="dropdown-item" href="{{ route('investors.ledger.create') }}">
              <i class="bi bi-journal-plus"></i> قيد عادي
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="{{ route('investors.ledger.split.create') }}">
              <i class="bi bi-columns-gap"></i> قيد مُجزّأ
            </a>
          </li>
          @role('admin')
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item" href="{{ route('investors.ledger.import.form') }}">
                <i class="bi bi-cloud-arrow-up"></i> {{ __('investors::investors.Import Investor Ledger') }}
              </a>
            </li>
          @endrole
        </ul>
      </div>
      @role('admin')
        <a href="{{ route('investors.import.form') }}" class="btn btn-outline-primary">
            <i class="bi bi-upload"></i> {{ __('investors::investors.Import Excel') }}
        </a>
      @endrole

      
      @if (session('failures') && count(session('failures')))
        <a href="{{ route('investors.import.export_failures') }}" class="btn btn-warning">
          <i class="bi bi-exclamation-triangle"></i> {{ __('investors::investors.Export Failures') }}
        </a>
      @endif
    </div>

    <div class="btn-group">
      <button type="button" class="btn btn-outline-dark dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
        📊 {{ __('investors::investors.Reports') }}
      </button>
      <ul class="dropdown-menu dropdown-menu-end text-end">
        <li>
          <a class="dropdown-item" href="{{ route('reports.investors.Allliquidity') }}">
            📄 {{ __('investors::investors.Investors Liquidity Report') }}
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="{{ route('reports.investors.outstanding') }}">
            📄 {{ __('investors::investors.Investors Outstanding Report') }}
          </a>
        </li>
      </ul>
    </div>

    <span class="ms-auto small text-muted">
      {{ __('investors::investors.Results') }}: <strong>{{ $investors->total() }}</strong>
    </span>

    <button class="btn btn-outline-secondary btn-sm" type="button"
            data-bs-toggle="collapse" data-bs-target="#filterBar"
            aria-expanded="false" aria-controls="filterBar">
      {{ __('investors::investors.Filter') }}
    </button>
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
          <a href="{{ route('investors.index') }}" class="btn btn-outline-secondary btn-sm w-100">{{ __('investors::investors.Clear') }}</a>
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
                        <th>{{ __('investors::investors.Name') }}</th>
                        <th>{{ __('investors::investors.Investment Start Date') }}</th>
                        <th>{{ __('Current Liquidity') }}</th>
                        <th>{{ __('investors::investors.Active Contracts') }}</th>
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
                                <div class="mt-3"><a href="{{ route('investors.create') }}" class="btn btn-sm btn-success">+ {{ __('investors::investors.Add First Investor') }}</a></div>
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
