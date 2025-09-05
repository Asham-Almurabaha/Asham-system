{{-- resources/views/customers/index.blade.php --}}
@extends('layouts.master')

@section('title', __('Customers List'))

@section('content')

<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">{{ __('Customers List') }}</h1>
    <nav><ol class="breadcrumb"><li class="breadcrumb-item active">{{ __('Customers') }}</li></ol></nav>
</div>

@php
    $allTotal    = (int)($customersTotalAll ?? 0);
    $allActive   = (int)($activeCustomersTotalAll ?? 0);
    $allInactive = max($allTotal - $allActive, 0);

    $activePct   = $allTotal > 0 ? round(($allActive   / $allTotal) * 100, 1) : 0;
    $inactivePct = $allTotal > 0 ? round(($allInactive / $allTotal) * 100, 1) : 0;

    $newThisMonthAll = (int)($newCustomersThisMonthAll ?? 0);
    $newThisWeekAll  = (int)($newCustomersThisWeekAll  ?? 0);
@endphp

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">



{{-- ====== General Cards ====== --}}
<div class="row g-4 mb-3" dir="rtl">
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
</div>

{{-- ====== Toolbar ====== --}}
<div class="card shadow-sm mb-3 ">
  <div class="card-body d-flex flex-wrap gap-2 align-items-center p-2">

    <div class="btn-group" role="group" aria-label="Actions">
      <a href="{{ route('customers.create') }}" class="btn btn-success">
        <i class="bi bi-plus-lg"></i> {{ __('Add Customer') }}
      </a>
      @role('admin')
        <a href="{{ route('customers.import.form') }}" class="btn btn-outline-primary">
            <i class="bi bi-upload"></i> {{ __('Import Excel') }}
        </a>
      @endrole

      {{-- Template button removed as requested --}}
    </div>

    <div class="btn-group">
      <button type="button" class="btn btn-outline-dark dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
        
        📊 {{ __('Reports') }}
      </button>
      <ul class="dropdown-menu dropdown-menu-end text-end">
        <li>
          <a class="dropdown-item" href="{{ route('reports.customers.delinquent') }}">
            <i class="bi bi-exclamation-triangle me-2 text-warning"></i>
            {{ __('Delinquent Customers') }}
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="{{ route('reports.customers.active') }}">
            <i class="bi bi-person-check me-2 text-success"></i> {{ __('Customers With Active Contracts') }}
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="{{ route('reports.customers.unpaid') }}">
            <i class="bi bi-cash-coin me-2 text-primary"></i>
            {{ __('Unpaid This Month') }}
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="{{ route('reports.customers.contracts') }}">
            <i class="bi bi-list-check me-2"></i>
            {{ __('Customers & Contracts Report') }}
          </a>
        </li>
      </ul>
    </div>

    <span class="ms-auto small text-muted">
      {{ __('Results') }}: <strong>{{ $customers->total() }}</strong>
    </span>

    <button class="btn btn-outline-secondary btn-sm" type="button"
            data-bs-toggle="collapse" data-bs-target="#filterBar"
            aria-expanded="false" aria-controls="filterBar">
      {{ __('Filter') }}
    </button>
  </div>

  <div class="collapse @if(request()->hasAny(['customer_q','national_id','phone'])) show @endif border-top" id="filterBar">
    <div class="card-body">
      <form id="filterForm" action="{{ route('customers.index') }}" method="GET" class="row gy-2 gx-2 align-items-end">
        {{-- Search by customer name only --}}
        <div class="col-12 col-md-4">
          <label class="form-label mb-1">{{ __('Customer (by name)') }}</label>
          <input type="text"
                 name="customer_q"
                 value="{{ request('customer_q') }}"
                 class="form-control form-control-sm auto-submit-input"
                 placeholder="{{ __('Type customer name...') }}">
        </div>

        {{-- Additional filters (optional) --}}
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">{{ __('National ID') }}</label>
          <input type="text" name="national_id" value="{{ request('national_id') }}"
                 class="form-control form-control-sm auto-submit-input" placeholder="1234567890">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">{{ __('Phone') }}</label>
          <input type="text" name="phone" value="{{ request('phone') }}"
                 class="form-control form-control-sm auto-submit-input" placeholder="+9665XXXXXXXX">
        </div>

        <div class="col-12 col-md-2">
          <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm w-100">{{ __('Clear') }}</a>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- ====== Table ====== --}}
<div class="card shadow-sm mb-3 ">
    <div class="card-body p-0">
        <div>
            <table class="table table-hover align-middle text-center mb-0">
                <thead class="table-light position-sticky top-0" style="z-index: 1;">
                    <tr>
                        <th style="width:60px">#</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('National ID') }}</th>
                        <th>{{ __('Phone') }}</th>
                        <th>{{ __('Number of Active Contracts') }}</th>
                        <th>{{ __('Total Remaining on Customer') }}</th>
                        <th>{{ __('Unpaid Installments This Month') }}</th>
                        <th>{{ __('Unpaid Amount This Month') }}</th>
                    </tr>
                </thead>
                <tbody>
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
                            <td dir="ltr">{{ $customer->national_id ?? '—' }}</td>
                            <td dir="ltr">{{ $customer->phone ?? '—' }}</td>

                                @php
                                    $endedStatusNames = ['مغلق','منتهي','سداد مبكر','مقفلة','مغلقة','Completed','Early Settlement'];
                                    $statusIdCol = null; $statusNameCol = null;
                                    foreach (['contract_status_id','status_id','state_id'] as $col) { if (Schema::hasColumn('contracts',$col)) { $statusIdCol = $col; break; } }
                                    foreach (['status','state','contract_status'] as $col) { if (Schema::hasColumn('contracts',$col)) { $statusNameCol = $col; break; } }
                                    $activeQ = $customer->contracts();
                                    if ($statusIdCol) {
                                        $endedIds = class_exists(\App\Models\ContractStatus::class) ? \App\Models\ContractStatus::whereIn('name',$endedStatusNames)->pluck('id')->all() : [];
                                        if (!empty($endedIds)) { $activeQ->whereNotIn($statusIdCol, $endedIds); }
                                    } elseif ($statusNameCol) {
                                        $activeQ->whereNotIn($statusNameCol, $endedStatusNames);
                                    } elseif (Schema::hasColumn('contracts','is_closed')) {
                                        $activeQ->where('is_closed',0);
                                    } elseif (Schema::hasColumn('contracts','closed_at')) {
                                        $activeQ->whereNull('closed_at');
                                    }
                                    $activeIds = $activeQ->pluck('id');
                                    $activeCount = $activeIds->count();
                                    $remainingSum = $activeCount ? \App\Models\ContractInstallment::whereIn('contract_id',$activeIds)->whereNull('payment_date')->sum('due_amount') : 0;
                                    $startMonth = \Carbon\Carbon::now()->startOfMonth();
                                    $endMonth = \Carbon\Carbon::now()->endOfMonth();
                                    $unpaidAmountThisMonth = $activeCount ? \App\Models\ContractInstallment::whereIn('contract_id',$activeIds)->whereBetween('due_date',[$startMonth,$endMonth])->whereNull('payment_date')->sum('due_amount') : 0;
                                    $unpaidCountThisMonth = $activeCount ? \App\Models\ContractInstallment::whereIn('contract_id',$activeIds)->whereBetween('due_date',[$startMonth,$endMonth])->whereNull('payment_date')->count() : 0;
                                @endphp
                            <td class="text-center">{{ $activeCount }}</td>
                            <td class="text-center">{{ number_format((float)$remainingSum, 2) }}</td>
                            <td class="text-center">{{ $unpaidCountThisMonth }}</td>
                            <td class="text-center">{{ number_format((float)$unpaidAmountThisMonth, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-5">
                                <div class="text-muted">
                                    {{ __('No matching results for your search.') }}
                                    <a href="{{ route('customers.index') }}" class="ms-1">{{ __('View All') }}</a>
                                </div>
                                <div class="mt-3">
                                    <a href="{{ route('customers.create') }}" class="btn btn-sm btn-success">
                                        + {{ __('Add First Customer') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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

