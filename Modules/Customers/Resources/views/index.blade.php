{{-- Modules/Customers/Resources/views/index.blade.php --}}
@extends('layouts.master')

@section('title', __('Customers List'))

@section('content')

@php
    $periodContext = (array) ($periodContext ?? []);
    $periodMonths  = (array) ($periodMonths ?? []);
    $periodYears   = (array) ($periodYears ?? []);

    $selectedPeriodMonth = request()->filled('period_month')
        ? (int) request('period_month')
        : (int) ($periodContext['month'] ?? now()->month);

    $selectedPeriodYear = request()->filled('period_year')
        ? (int) request('period_year')
        : (int) ($periodContext['year'] ?? now()->year);

    $periodLabel = $periodContext['label'] ?? null;
@endphp

<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">{{ __('Customers List') }}</h1>
    <nav><ol class="breadcrumb"><li class="breadcrumb-item active">{{ __('Customers') }}</li></ol></nav>
</div>

{{-- @php
    $allTotal    = (int)($customersTotalAll ?? 0);
    $allActive   = (int)($activeCustomersTotalAll ?? 0);
    $allInactive = max($allTotal - $allActive, 0);

    $activePct   = $allTotal > 0 ? round(($allActive   / $allTotal) * 100, 1) : 0;
    $inactivePct = $allTotal > 0 ? round(($allInactive / $allTotal) * 100, 1) : 0;

    $newThisMonthAll = (int)($newCustomersThisMonthAll ?? 0);
    $newThisWeekAll  = (int)($newCustomersThisWeekAll  ?? 0);
@endphp --}}

{{-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"> --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">



{{-- ====== General Cards ====== --}}
{{-- <div class="row g-4 mb-3" dir="rtl">
    <div class="col-12 col-md-3">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-people fs-4 text-primary"></i></div>
                <div class="flex-grow-1">
                    <div class="subnote">{{ __('Total Customers — All System') }}</div>
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
                    <div class="subnote">{{ __('Active Customers') }}</div>
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
                    <div class="subnote">{{ __('Inactive') }}</div>
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
                    <div class="subnote">{{ __('New Customers This Month') }}</div>
                    <div class="kpi-value fw-bold">{{ number_format($newThisMonthAll) }}</div>
                    <div class="subnote">{{ __('This Week') }}: {{ number_format($newThisWeekAll) }}</div>
                </div>
            </div>
        </div>
    </div>
</div> --}}

{{-- ====== Toolbar ====== --}}
<div class="card shadow-sm mb-3 ">
  <div class="card-body d-flex flex-wrap gap-2 align-items-center p-2">

    <div class="btn-group" role="group" aria-label="Actions">
      <x-button.action href="{{ route('customers.create') }}" variant="success">
        <i class="bi bi-plus-lg"></i> {{ __('Add Customer') }}
      </x-button.action>
      <x-button.action href="{{ route('customers.dashboard') }}" variant="dark" :outline="true">
        <i class="bi bi-speedometer2"></i> {{ __('customers::messages.View Dashboard') }}
      </x-button.action>
      <x-button.action type="submit" variant="primary" :outline="true" form="customers-refresh-statuses-form" onclick="return confirm('{{ __('customers::messages.Refresh Customer Statuses Confirmation') }}');">
        <i class="bi bi-arrow-clockwise"></i> {{ __('customers::messages.Refresh Customer Statuses') }}
      </x-button.action>



      {{-- Template button removed as requested --}}
    </div>

    <form id="customers-refresh-statuses-form" method="POST" action="{{ route('customers.refresh-statuses') }}" class="d-none">
      @csrf
    </form>

    <div class="ms-auto d-flex flex-wrap align-items-center gap-2">
      @if($periodLabel)
        <span class="badge bg-light text-dark d-inline-flex align-items-center gap-1">
          <i class="bi bi-calendar-event"></i>
          <span>{{ $periodLabel }}</span>
        </span>
      @endif
      <span class="small text-muted">
        {{ __('Results') }}: <strong>{{ $customers->total() }}</strong>
      </span>
    </div>

    <x-button.action type="button" variant="secondary" :outline="true" size="sm" data-bs-toggle="collapse" data-bs-target="#filterBar" aria-expanded="false" aria-controls="filterBar">
      {{ __('Filter') }}
    </x-button.action>
  </div>

  <div class="collapse @if(request()->hasAny(['customer_q','national_id','phone','period_month','period_year'])) show @endif border-top" id="filterBar">
    <div class="card-body">
      <form id="filterForm" action="{{ route('customers.index') }}" method="GET" class="row gy-2 gx-2 align-items-end">
        {{-- Search by customer name only --}}
        <div class="col-12 col-md-4">
          <label class="form-label mb-1">{{ __('Customer (by name)') }}</label>
          <input type="text"
                 name="customer_q"
                 value="{{ request('customer_q') }}"
                 class="form-control form-control-sm auto-submit-input"
                 placeholder="{{ __('customers::messages.Type customer name...') }}">
        </div>

        {{-- Additional filters (optional) --}}
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">{{ __('National ID') }}</label>
          <input type="text" name="national_id" value="{{ request('national_id') }}"
                 class="form-control form-control-sm auto-submit-input" placeholder="{{ __('1234567890') }}">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">{{ __('Phone') }}</label>
          <input type="text" name="phone" value="{{ request('phone') }}"
                 class="form-control form-control-sm auto-submit-input" placeholder="{{ __('+9665XXXXXXXX') }}">
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label mb-1">{{ __('Month') }}</label>
          <select name="period_month" class="form-select form-select-sm auto-submit-input">
            @foreach($periodMonths as $value => $label)
              <option value="{{ $value }}" @selected($selectedPeriodMonth === (int) $value)>{{ $label }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label mb-1">{{ __('Year') }}</label>
          <select name="period_year" class="form-select form-select-sm auto-submit-input">
            @foreach($periodYears as $value => $label)
              <option value="{{ $value }}" @selected($selectedPeriodYear === (int) $value)>{{ $label }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-12 col-md-2">
          <x-button.action href="{{ route('customers.index') }}" variant="secondary" :outline="true" size="sm" class="w-100">{{ __('Clear') }}</x-button.action>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- ====== Table ====== --}}
<div class="card shadow-sm mb-3 ">
    <div class="card-body p-0">
        <div>
            <x-table head-class="table-light position-sticky top-0" class="text-center">
                <x-slot name="head">
                    <tr>
                        <th style="width:60px">#</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('customers::messages.Customer Status') }}</th>
                        <th>{{ __('National ID') }}</th>
                        <th>{{ __('Phone') }}</th>
                        <th>{{ __('Number of Active Contracts') }}</th>
                        <th>{{ __('Total Remaining on Customer') }}</th>
                        <th>{{ __('Unpaid Installments This Month') }}</th>
                        <th>{{ __('Unpaid Amount This Month') }}</th>
                    </tr>
                </x-slot>
                @forelse ($customers as $customer)
                    <tr>
                        <td class="text-muted">
                            {{ $loop->iteration + ($customers->currentPage() - 1) * $customers->perPage() }}
                        </td>
                        <td class="text-start">
                            <a href="{{ route('customers.show', $customer) }}" class="text-decoration-none fw-bold text-dark hover-primary">
                                {{ $customer->name }}
                            </a>
                        </td>
                        <td>{{ optional($customer->customerStatus)->name ?? __('Undefined') }}</td>
                        <td dir="ltr">{{ $customer->national_id ?? '—' }}</td>
                        <td dir="ltr">{{ $customer->phone ?? '—' }}</td>
                        <td class="text-center">{{ number_format($customer->active_contracts_count ?? 0) }}</td>
                        <td class="text-center">{{ number_format((float) ($customer->remaining_balance_total ?? 0), 2) }}</td>
                        <td class="text-center">{{ number_format((int) ($customer->unpaid_installments_this_month ?? 0)) }}</td>
                        <td class="text-center">{{ number_format((float) ($customer->unpaid_amount_this_month ?? 0), 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="py-5">
                            <div class="text-muted">
                                {{ __('No matching results for your search.') }}
                                <a href="{{ route('customers.index') }}" class="ms-1">{{ __('View All') }}</a>
                            </div>
                            <div class="mt-3">
                                <x-button.action href="{{ route('customers.create') }}" variant="success" size="sm">
                                    + {{ __('Add First Customer') }}
                                </x-button.action>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-table>
        </div>
    </div>

    @if($customers->hasPages())
    <div class="card-footer bg-white">
        {{ $customers->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Tooltip للصور
    const tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el, {container: 'body'}));

    // Auto-submit للمدخلات النصية مع تأخير بسيط
    let typingTimer;
    document.querySelectorAll('.auto-submit-input').forEach(el => {
        el.addEventListener('input', function() {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                document.getElementById('filterForm').submit();
            }, 600);
        });
    });
});
</script>
@endpush

