@extends('layouts.master')

@section('title', __('View Customer Data'))

@section('content')
<div class="container py-3" dir="rtl">

    {{-- Bootstrap Icons (If not added in the layout) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    @php
        /**
         * The values coming from the controller:
         * - $contractsSummary: ['total','active','finished','other','pct_active','pct_finished','pct_other']
         * - $statusesBreakdown: [['id'=>?, 'name'=>'...', 'count'=>n, 'total_value_sum'=>.., 'formatted'=>..], ...]
         * - $installments: Modules\Customers\DTOS\InstallmentsSummary (object)
         */

        $banks = collect($banks ?? []);
        $safes = collect($safes ?? []);
        $hasPaymentAccounts = $banks->count() > 0 || $safes->count() > 0;
        $defaultPayDate = now()->format('Y-m-d');

        // ====== Quick summary from service data ======
        $cs = $contractsSummary ?? ['total'=>0,'active'=>0,'finished'=>0,'other'=>0];
        $contractsCount       = (int)($cs['total']    ?? 0);
        $activeContractsCount = (int)($cs['active']   ?? 0);

        $pct = fn($k) => isset($cs["pct_$k"]) ? (float)$cs["pct_$k"] : ($cs['total']>0 ? round(($cs[$k] ?? 0)/$cs['total']*100, 1) : 0);
        $activePct   = $pct('active');
        $finishedPct = $pct('finished');
        $otherPct    = $pct('other');

        // Contract status distribution
        $sb = collect($statusesBreakdown ?? [])->values();
        $sb_total = max(1, (int) ($sb->sum('count') ?: $contractsCount)); // To avoid division by zero

        // Installments summary (object or array as backup)
        $instObj = $installments ?? null;
        $i_total_installments = is_object($instObj) ? $instObj->total_installments : (int)($instObj['total_installments'] ?? 0);
        $i_due_amount         = is_object($instObj) ? $instObj->total_due_amount   : (float)($instObj['total_due_amount']   ?? 0);
        $i_paid_amount        = is_object($instObj) ? $instObj->total_paid_amount  : (float)($instObj['total_paid_amount']  ?? 0);
        $i_unpaid_amount      = is_object($instObj) ? $instObj->total_unpaid_amount: (float)($instObj['total_unpaid_amount']?? 0);
        $i_overdue_count      = is_object($instObj) ? $instObj->overdue_count      : (int)  ($instObj['overdue_count']      ?? 0);
        $i_overdue_amount     = is_object($instObj) ? $instObj->overdue_amount     : (float)($instObj['overdue_amount']     ?? 0);
        $next_due_date        = is_object($instObj) ? $instObj->next_due_date      : ($instObj['next_due_date'] ?? null);
        $last_payment_date    = is_object($instObj) ? $instObj->last_payment_date  : ($instObj['last_payment_date'] ?? null);

        $nf = fn($n,$d=2) => is_null($n) ? '—' : number_format((float)$n, $d);

        // Monthly payment report context
        $periodContextData   = (array) ($periodContext ?? []);
        $periodMonthsOptions = (array) ($periodMonths ?? []);
        $periodYearsOptions  = (array) ($periodYears ?? []);

        $monthlyReport            = (array) ($monthlyPaymentReport ?? []);
        $monthlyContractsColl     = collect($monthlyReport['contracts'] ?? []);
        $monthlyContractsCount    = (int) ($monthlyReport['contracts_count'] ?? $monthlyContractsColl->count());
        $monthlyInstallmentsCount = (int) ($monthlyReport['installments_count'] ?? $monthlyContractsColl->sum(fn($row) => (int) ($row['installment_count'] ?? 0)));
        $monthlyTotalDue          = (float) ($monthlyReport['total_due'] ?? 0.0);
        $monthlyTotalPaid         = (float) ($monthlyReport['total_paid'] ?? 0.0);
        $monthlyTotalRemaining    = (float) ($monthlyReport['total_remaining'] ?? max($monthlyTotalDue - $monthlyTotalPaid, 0));
        $monthlyPaidPct           = $monthlyTotalDue > 0 ? round(($monthlyTotalPaid / $monthlyTotalDue) * 100, 1) : 0.0;
        $monthlyPeriodLabel       = (string) ($monthlyReport['period_label'] ?? ($periodContextData['label'] ?? ''));
        $monthlyPeriodStart       = $monthlyReport['period_start'] ?? ($periodContextData['start'] ?? null);
        $monthlyPeriodEnd         = $monthlyReport['period_end'] ?? ($periodContextData['end'] ?? null);

        $formatPeriodValue = function ($value) {
            if ($value instanceof \Carbon\CarbonInterface) {
                return $value->format('Y-m-d');
            }
            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d');
            }

            return $value ? (string) $value : null;
        };

        $monthlyPeriodStartLabel = $formatPeriodValue($monthlyPeriodStart);
        $monthlyPeriodEndLabel   = $formatPeriodValue($monthlyPeriodEnd);

        if (!$monthlyPeriodLabel && $monthlyPeriodStartLabel && $monthlyPeriodEndLabel) {
            $monthlyPeriodLabel = $monthlyPeriodStartLabel . ' — ' . $monthlyPeriodEndLabel;
        }

        $selectedPeriodMonth = (int) ($periodContextData['month'] ?? ($monthlyReport['month'] ?? now()->month));
        $selectedPeriodYear  = (int) ($periodContextData['year'] ?? ($monthlyReport['year'] ?? now()->year));
    @endphp

    {{-- ====== HERO ====== --}}
    <div class="profile-hero mb-3">
        <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar">
                    {{ mb_strtoupper(mb_substr($customer->name ?? '؟', 0, 1)) }}
                </div>
                <div>
                    <h3 class="mb-0 fw-bold fs-2 text-dark hover-primary">
                        {{ $customer->name }}
                    </h3>
                    <div class="small text-muted-2 mt-1">
                        <span class="chip me-1"><i class="bi bi-badge-ad"></i> {{ optional($customer->title)->name ?? '—' }}</span>
                        <span class="chip me-1"><i class="bi bi-flag"></i> {{ optional($customer->nationality)->name ?? '—' }}</span>
                        <span class="chip me-1">
                            <i class="bi bi-tag"></i>
                            {{ optional($customer->customerStatus)->name ?? __('Undefined') }}
                        </span>
                        <span class="chip"><i class="bi bi-hash"></i> {{ __('ID') }}: {{ $customer->id }}</span>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <x-button.action href="{{ route('customers.reports.monthly.print', array_merge(['customer' => $customer->id], request()->only(['period_month','period_year']))) }}" variant="secondary" target="_blank">
                    <i class="bi bi-printer me-1"></i> {{ __('customers::messages.Print Monthly Report') }}
                </x-button.action>
                <x-button.action href="{{ route('customers.edit', $customer) }}" variant="primary">
                    <i class="bi bi-pencil-square me-1"></i> {{ __('Edit') }}
                </x-button.action>
                <x-button.action href="{{ route('customers.index') }}" variant="secondary" :outline="true">
                    <i class="bi bi-arrow-right-circle me-1"></i> {{ __('Back to List') }}
                </x-button.action>
            </div>
        </div>
    </div>

    @if(($claimCards['show'] ?? false))
        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-4">
                <div class="kpi-card p-3 h-100">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="kpi-icon"><i class="bi bi-exclamation-diamond-fill fs-5 text-danger"></i></div>
                        <div class="fw-bold text-muted">{{ __('Total Claims Value') }}</div>
                    </div>
                    <div class="fs-3 fw-bold text-danger">{{ number_format($claimCards['total'] ?? 0, 2) }}</div>
                    <div class="small text-muted">{{ __('Value of claims on required/raised contracts') }}</div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="kpi-card p-3 h-100">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="kpi-icon"><i class="bi bi-cash-stack fs-5 text-success"></i></div>
                        <div class="fw-bold text-muted">{{ __('Claims Paid') }}</div>
                    </div>
                    <div class="fs-3 fw-bold text-success">{{ number_format($claimCards['paid'] ?? 0, 2) }}</div>
                    <div class="small text-muted">{{ __('Collected to date (investor + office)') }}</div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="kpi-card p-3 h-100">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="kpi-icon"><i class="bi bi-bullseye fs-5 text-primary"></i></div>
                        <div class="fw-bold text-muted">{{ __('Claims Remaining') }}</div>
                    </div>
                    @php $claimsRemain = (float) ($claimCards['remaining'] ?? 0); @endphp
                    <div class="fs-3 fw-bold {{ $claimsRemain > 0 ? 'text-danger' : 'text-muted' }}">{{ number_format($claimsRemain, 2) }}</div>
                    <div class="small text-muted">{{ __('Outstanding balance on required/raised contracts') }}</div>
                </div>
            </div>
        </div>
    @endif

    {{-- ====== Contracts and installments cards (from customer details service) ====== --}}
    @php
        $cs_active   = (int)($cs['active']   ?? 0);
        $cs_finished = (int)($cs['finished'] ?? 0);
        $cs_other    = (int)($cs['other']    ?? 0);
    @endphp

    {{-- Contracts summary row --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-files fs-5 text-primary"></i></div>
                    <div class="fw-bold text-muted">{{ __('Total Contracts') }}</div>
                </div>
                <div class="fs-2 fw-bold">{{ number_format($contractsCount) }}</div>
                <div class="small text-muted">{{ __('customers::messages.All contracts associated with the customer') }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-person-check fs-5 text-success"></i></div>
                    <div class="fw-bold text-muted">{{ __('Active Contracts') }}</div>
                </div>
                <div class="fs-2 fw-bold text-success">{{ number_format($cs_active) }}</div>
                <div class="small text-muted">{{ __('Percentage') }}: {{ number_format($activePct,1) }}%</div>
                <div class="progress mt-2" style="height:8px;">
                    <div class="progress-bar" role="progressbar" style="width: {{ $activePct }}%"></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-flag-fill fs-5 text-secondary"></i></div>
                    <div class="fw-bold text-muted">{{ __('Finished Contracts') }}</div>
                </div>
                <div class="fs-2 fw-bold">{{ number_format($cs_finished) }}</div>
                <div class="small text-muted">{{ __('Percentage') }}: {{ number_format($finishedPct,1) }}%</div>
                <div class="progress mt-2" style="height:8px;">
                    <div class="progress-bar bg-secondary" role="progressbar" style="width: {{ $finishedPct }}%"></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-three-dots fs-5 text-warning"></i></div>
                    <div class="fw-bold text-muted">{{ __('Other') }}</div>
                </div>
                <div class="fs-2 fw-bold">{{ number_format($cs_other) }}</div>
                <div class="small text-muted">{{ __('Percentage') }}: {{ number_format($otherPct,1) }}%</div>
                <div class="progress mt-2" style="height:8px;">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $otherPct }}%"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Row: Installments summary + status distribution --}}
    <div class="row g-3 mb-3">
        {{-- Installments summary --}}
        <div class="col-12 col-xl-8">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="kpi-icon"><i class="bi bi-cash-coin fs-5 text-primary"></i></div>
                        <div class="fw-bold text-muted">{{ __('Installments Summary') }}</div>
                    </div>
                    <span class="badge text-bg-light">
                        {{ __('Total Installments') }}: {{ number_format($i_total_installments) }} /
                        {{ __('Total Due') }}: {{ $nf($i_due_amount) }}
                    </span>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="small text-muted mb-1">{{ __('Paid') }}</div>
                            <div class="fw-bold text-success">{{ $nf($i_paid_amount) }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="small text-muted mb-1">{{ __('Unpaid') }}</div>
                            <div class="fw-bold">{{ $nf($i_unpaid_amount) }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="small text-muted mb-1">{{ __('Overdue') }}</div>
                            <div class="fw-bold text-danger">
                                {{ number_format($i_overdue_count) }} {{ __('Installment') }} — {{ $nf($i_overdue_amount) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-12 col-md-6">
                        <div class="border rounded p-2 h-100">
                            <div class="small text-muted mb-1">{{ __('Next Installment') }}</div>
                            <div class="fw-bold">{{ $next_due_date ? \Carbon\Carbon::parse($next_due_date)->format('Y-m-d') : '—' }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="border rounded p-2 h-100">
                            <div class="small text-muted mb-1">{{ __('Last Payment') }}</div>
                            <div class="fw-bold">{{ $last_payment_date ? \Carbon\Carbon::parse($last_payment_date)->format('Y-m-d') : '—' }}</div>
                        </div>
                    </div>
                </div>

                @php
                    // Percentage paid/unpaid of total due
                    $paidPct   = $i_due_amount > 0 ? round(($i_paid_amount / $i_due_amount) * 100) : 0;
                    $unpaidPct = $i_due_amount > 0 ? round(($i_unpaid_amount / $i_due_amount) * 100) : 0;
                @endphp
                <div class="mt-3">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>{{ __('Percentage Paid of Total Due') }}</span>
                        <span>{{ $paidPct }}%</span>
                    </div>
                    <div class="progress" style="height:8px;">
                        <div class="progress-bar bg-success" style="width: {{ $paidPct }}%"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mt-2 mb-1">
                        <span>{{ __('Percentage Unpaid of Total Due') }}</span>
                        <span>{{ $unpaidPct }}%</span>
                    </div>
                    <div class="progress" style="height:8px;">
                        <div class="progress-bar bg-secondary" style="width: {{ $unpaidPct }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Contract status distribution --}}
        <div class="col-12 col-xl-4">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-pie-chart fs-5 text-warning"></i></div>
                    <div class="fw-bold text-muted">{{ __('Contract Status Distribution') }}</div>
                </div>

                @if($sb->isNotEmpty())
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($sb as $st)
                            @php
                                $cnt  = (int)($st['count'] ?? 0);
                                $name = (string)($st['name'] ?? __('Undefined'));
                                $pct  = $cnt>0 ? round(($cnt / $sb_total) * 100) : 0;
                            @endphp
                            <span class="badge text-bg-light">
                                {{ $name }} — {{ number_format($cnt) }} ({{ $pct }}%)
                            </span>
                        @endforeach
                    </div>
                @else
                    <div class="text-muted">{{ __('No sufficient data to display distribution.') }}</div>
                @endif
            </div>
        </div>
    </div>
    {{-- ====== End of contracts and installments cards ====== --}}

    {{-- ====== Monthly payment report for this customer ====== --}}
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div class="d-flex flex-column gap-2">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="fw-bold fs-5">{{ __('Monthly Payment Report') }}</div>
                    @if(!empty($monthlyPeriodLabel))
                        <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1">
                            <i class="bi bi-calendar-event"></i>
                            <span>{{ $monthlyPeriodLabel }}</span>
                        </span>
                    @endif
                </div>
                <div class="small text-muted">{{ __('Contracts with recorded payments during the selected period.') }}</div>
            </div>
            <form action="{{ route('customers.show', $customer) }}" method="GET" class="d-flex flex-wrap align-items-end justify-content-end gap-2">
                @foreach(request()->except(['period_month','period_year','page']) as $k => $v)
                    @if(is_array($v))
                        @foreach($v as $vv)
                            <input type="hidden" name="{{ $k }}[]" value="{{ $vv }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endif
                @endforeach
                <div class="d-flex flex-column">
                    <label class="form-label small mb-1" for="period_month">{{ __('Month') }}</label>
                    <select name="period_month" id="period_month" class="form-select form-select-sm">
                        @foreach($periodMonthsOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedPeriodMonth === (int) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex flex-column">
                    <label class="form-label small mb-1" for="period_year">{{ __('Year') }}</label>
                    <select name="period_year" id="period_year" class="form-select form-select-sm">
                        @foreach($periodYearsOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedPeriodYear === (int) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex align-items-end gap-2">
                    <x-button.action type="submit" variant="primary" :outline="true" size="sm">
                        <i class="bi bi-save2 me-1"></i> {{ __('Update') }}
                    </x-button.action>
                    <x-button.action href="{{ route('customers.show', $customer) }}" variant="secondary" :outline="true" size="sm">
                        {{ __('Clear') }}
                    </x-button.action>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-4">
                    <div class="kpi-card p-3 h-100">
                        <div class="small text-muted mb-1">{{ __('Contracts with payments') }}</div>
                        <div class="fs-3 fw-bold">{{ number_format($monthlyContractsCount) }}</div>
                        <div class="small text-muted">{{ __('Contracts in period') }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="kpi-card p-3 h-100">
                        <div class="small text-muted mb-1">{{ __('Installments Recorded') }}</div>
                        <div class="fs-3 fw-bold">{{ number_format($monthlyInstallmentsCount) }}</div>
                        <div class="small text-muted">{{ __('Installments in period') }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="kpi-card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small text-muted">{{ __('Total Due in Period') }}</span>
                            <span class="fw-bold">{{ number_format($monthlyTotalDue, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small text-muted">{{ __('Total Paid in Period') }}</span>
                            <span class="fw-bold text-success">{{ number_format($monthlyTotalPaid, 2) }}</span>
                        </div>
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar" style="width: {{ $monthlyPaidPct }}%"></div>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mt-1">
                            <span>{{ __('Paid Percentage') }}: {{ number_format($monthlyPaidPct,1) }}%</span>
                            <span>{{ __('Remaining') }}: {{ number_format($monthlyTotalRemaining, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                @if($monthlyContractsColl->isNotEmpty())
                    <x-table head-class="table-light">
                        <x-slot name="head">
                            <tr>
                                <th style="width:160px">{{ __('Contract Number') }}</th>
                                <th style="width:140px">{{ __('Start Date') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end" style="width:140px">{{ __('Installments') }}</th>
                                <th class="text-end" style="width:140px">{{ __('Due Amount') }}</th>
                                <th class="text-end" style="width:140px">{{ __('Paid Amount') }}</th>
                                <th class="text-end" style="width:140px">{{ __('Remaining') }}</th>
                                <th style="width:140px">{{ __('Last Payment') }}</th>
                            </tr>
                        </x-slot>
                        @foreach($monthlyContractsColl as $row)
                            @php
                                $contractNumber   = (string) ($row['contract_number'] ?? ($row['contract_id'] ?? '—'));
                                $contractStatus   = $row['status_name'] ?? '—';
                                $contractStart    = $row['start_date'] ?? null;
                                $installmentsCnt  = (int) ($row['installment_count'] ?? 0);
                                $dueSum           = (float) ($row['due_sum'] ?? 0.0);
                                $paidSum          = (float) ($row['paid_sum'] ?? 0.0);
                                $remainingSum     = (float) ($row['remaining_sum'] ?? max($dueSum - $paidSum, 0));
                                $lastPaymentDate  = $row['last_payment_date'] ?? null;
                            @endphp
                            <tr>
                                <td>{{ $contractNumber }}</td>
                                <td>{{ $contractStart ?: '—' }}</td>
                                <td>{{ $contractStatus ?: '—' }}</td>
                                <td class="text-end">{{ number_format($installmentsCnt) }}</td>
                                <td class="text-end">{{ number_format($dueSum, 2) }}</td>
                                <td class="text-end text-success">{{ number_format($paidSum, 2) }}</td>
                                <td class="text-end">{{ number_format($remainingSum, 2) }}</td>
                                <td>{{ $lastPaymentDate ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </x-table>
                @else
                    <div class="text-muted text-center py-4">{{ __('No payments were recorded during this period for the customer contracts.') }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ====== Active contracts table: Paid and remaining ====== --}}
    @php
        // We try to fetch the list of active contracts from more than one path to ensure compatibility
        $activeList = collect($activeContracts ?? ($details->active ?? ($customerDetails['contracts']['active'] ?? [])))->values();

        $totDue = 0.0; $totPaid = 0.0; $totRemain = 0.0;
    @endphp

    <div class="card shadow-sm mb-3 kpi-card">
        <div class="card-header bg-white fw-bold d-flex align-items-center justify-content-between">
            <span><i class="bi bi-card-checklist me-1"></i>{{ __('Active Contracts') }}</span>
            <span class="badge text-bg-light">{{ __('Count') }}: {{ number_format($activeList->count()) }}</span>
        </div>

        <div class="card-body p-0">
            @if($activeList->isNotEmpty())
                <x-table head-class="table-light" foot-class="table-light">
                    <x-slot name="head">
                        <tr>
                            <th style="width:140px">{{ __('Contract Number') }}</th>
                            <th style="width:120px">{{ __('Start Date') }}</th>
                            <th>{{ __('Product Type') }}</th>
                            <th class="text-end" style="width:140px">{{ __('Total Due') }}</th>
                            <th class="text-end" style="width:140px">{{ __('Paid') }}</th>
                            <th class="text-end" style="width:140px">{{ __('Remaining') }}</th>
                            <th class="text-center" style="width:150px">{{ __('Actions') }}</th>
                        </tr>
                    </x-slot>
                    @foreach($activeList as $row)
                        @php
                            $isObj   = is_object($row);
                            $cid     = $isObj ? ($row->id ?? null) : ($row['id'] ?? null);
                            $cno     = $isObj ? ($row->contract_number ?? '') : ($row['contract_number'] ?? '');
                            $sdate   = $isObj ? ($row->start_date ?? null)     : ($row['start_date'] ?? null);
                            $ptype   = $isObj
                                        ? ($row->product_type_name ?? ($row->product_type->name ?? null))
                                        : ($row['product_type_name'] ?? ($row['product_type']['name'] ?? null));
                    
                            // Reading values whether they are direct properties or within installments[]
                            $due     = $isObj ? ($row->due_sum ?? 0)     : ($row['due_sum'] ?? ($row['installments']['due_sum'] ?? 0));
                            $paid    = $isObj ? ($row->paid_sum ?? 0)    : ($row['paid_sum'] ?? ($row['installments']['paid_sum'] ?? 0));
                            $remain  = $isObj ? ($row->remaining_amount ?? $row->unpaid_sum ?? 0)
                                              : ($row['remaining_amount'] ?? ($row['unpaid_sum'] ?? ($row['installments']['unpaid_sum'] ?? 0)));
                    
                            $totDue   += (float)$due;
                            $totPaid  += (float)$paid;
                            $totRemain+= (float)$remain;
                        @endphp
                        <tr>
                            <td class="fw-semibold">
                                @if(!empty($cid))
                                    <a href="{{ route('contracts.show', $cid) }}" class="text-decoration-none text-dark hover-primary fw-bold">{{ $cno }}</a>
                                @else
                                    {{ $cno }}
                                @endif
                            </td>
                            <td>{{ $sdate ? \Carbon\Carbon::parse($sdate)->format('Y-m-d') : '—' }}</td>
                            <td class="text-truncate" style="max-width:240px">{{ $ptype ?? '—' }}</td>
                            <td class="text-end">{{ $nf($due) }}</td>
                            <td class="text-end text-success">{{ $nf($paid) }}</td>
                            <td class="text-end {{ ($remain ?? 0)>0 ? 'text-danger' : 'text-muted' }}">{{ $nf($remain) }}</td>
                            <td class="text-center">
                                @if($cid && ($remain ?? 0) > 0)
                                    <x-button.action
                                        type="button"
                                        variant="success"
                                        data-bs-toggle="modal"
                                        data-bs-target="#customerContractPayModal"
                                        data-contract-id="{{ $cid }}"
                                        data-contract-number="{{ $cno }}"
                                        data-contract-remaining="{{ number_format((float) $remain, 2, '.', '') }}"
                                        data-contract-due="{{ number_format((float) $due, 2, '.', '') }}"
                                        data-contract-paid="{{ number_format((float) $paid, 2, '.', '') }}"
                                    >
                                        💰 سداد
                                    </x-button.action>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    <x-slot name="footer">
                        <tr>
                            <th colspan="3" class="text-end">{{ __('Total') }}</th>
                            <th class="text-end">{{ $nf($totDue) }}</th>
                            <th class="text-end text-success">{{ $nf($totPaid) }}</th>
                            <th class="text-end {{ $totRemain>0 ? 'text-danger' : 'text-muted' }}">{{ $nf($totRemain) }}</th>
                            <th></th>
                        </tr>
                    </x-slot>
                </x-table>
            @else
                <div class="p-3 text-muted">{{ __('No active contracts to display.') }}</div>
            @endif
        </div>
    </div>
    {{-- ====== End of active contracts table ====== --}}

    {{-- ====== Pay contract modal (mirrors contracts show payment logic) ====== --}}
    <div class="modal fade" id="customerContractPayModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="customerContractPayForm" action="{{ route('installments.pay') }}" method="POST">
                    @csrf
                    <input type="hidden" name="contract_id" id="customer_pay_contract_id">
                    <div class="modal-header">
                        <h5 class="modal-title">💰 سداد العقد</h5>
                        <x-button.action type="button" :unstyled="true" class="btn-close" data-bs-dismiss="modal"></x-button.action>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-light border">
                            <div class="fw-semibold">رقم العقد: <span id="customer_pay_contract_number">—</span></div>
                            <div class="small text-muted mt-2">
                                <div>إجمالي مستحق: <span id="customer_pay_due">0.00</span></div>
                                <div>المدفوع: <span id="customer_pay_paid">0.00</span></div>
                                <div>المتبقي: <span id="customer_pay_remaining">0.00</span></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">المبلغ المدفوع</label>
                            <input
                                type="number"
                                name="payment_amount"
                                id="customer_pay_amount"
                                step="0.01"
                                min="0"
                                class="form-control"
                                value="0.00"
                                required
                            >
                            <small class="text-muted" id="customer_pay_amount_hint">أقصى مبلغ مسموح: <span id="customer_pay_amount_max">0.00</span></small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">تاريخ السداد</label>
                            <input
                                type="text"
                                name="payment_date"
                                id="customer_pay_date"
                                class="form-control js-date"
                                value="{{ $defaultPayDate }}"
                                data-default-date="{{ $defaultPayDate }}"
                                placeholder="YYYY-MM-DD"
                                autocomplete="off"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="customer_account_picker_pay">الحساب</label>
                            <select
                                id="customer_account_picker_pay"
                                class="form-select"
                                {{ $hasPaymentAccounts ? 'required' : 'disabled' }}
                            >
                                <option value="" selected disabled>اختر حسابًا</option>
                                <optgroup label="الحسابات البنكية">
                                    @foreach ($banks as $bank)
                                        <option value="bank:{{ $bank->id }}">{{ $bank->name }}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="الخزن">
                                    @foreach ($safes as $safe)
                                        <option value="safe:{{ $safe->id }}">{{ $safe->name }}</option>
                                    @endforeach
                                </optgroup>
                            </select>
                            <input type="hidden" name="bank_account_id" id="customer_bank_account_id_pay">
                            <input type="hidden" name="safe_id" id="customer_safe_id_pay">
                            <div class="form-text">اختر بنكًا أو خزنة — لا يمكن الجمع بينهما في نفس السداد.</div>
                        </div>

                        @if(!$hasPaymentAccounts)
                            <div class="alert alert-warning">لا توجد حسابات بنكية أو خزائن مضافة بعد. الرجاء إضافة مصدر تحصيل من الإعدادات المالية.</div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label">ملاحظات (اختياري)</label>
                            <textarea name="notes" id="customer_pay_notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <x-button.action type="submit" variant="success">
                            <i class="bi bi-check2-circle me-1"></i> @lang('app.Save')
                        </x-button.action>
                        <x-button.action type="button" variant="secondary" data-bs-dismiss="modal">@lang('app.Cancel')</x-button.action>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.syncAccountHiddenGeneric !== 'function') {
                window.syncAccountHiddenGeneric = function (pickerId, bankHiddenId, safeHiddenId) {
                    const picker = document.getElementById(pickerId);
                    const bankH = document.getElementById(bankHiddenId);
                    const safeH = document.getElementById(safeHiddenId);

                    if (!picker || !bankH || !safeH) {
                        return;
                    }

                    const value = picker.value || '';
                    bankH.value = '';
                    safeH.value = '';

                    if (!value) {
                        return;
                    }

                    const parts = value.split(':');
                    const type = parts[0];
                    const id = parts[1] || '';

                    if (type === 'bank') {
                        bankH.value = id;
                    } else if (type === 'safe') {
                        safeH.value = id;
                    }
                };
            }

            const payModalEl = document.getElementById('customerContractPayModal');
            if (!payModalEl) {
                return;
            }

            const amountInput = document.getElementById('customer_pay_amount');
            const amountMaxLabel = document.getElementById('customer_pay_amount_max');
            const contractIdInput = document.getElementById('customer_pay_contract_id');
            const contractNumberEl = document.getElementById('customer_pay_contract_number');
            const dueEl = document.getElementById('customer_pay_due');
            const paidEl = document.getElementById('customer_pay_paid');
            const remainingEl = document.getElementById('customer_pay_remaining');
            const accountPicker = document.getElementById('customer_account_picker_pay');
            const dateInput = document.getElementById('customer_pay_date');

            payModalEl.addEventListener('show.bs.modal', function (event) {
                const trigger = event.relatedTarget;
                if (!trigger) {
                    return;
                }

                const contractId = trigger.getAttribute('data-contract-id') || '';
                const contractNumber = trigger.getAttribute('data-contract-number') || '—';
                const remaining = parseFloat(trigger.getAttribute('data-contract-remaining') || '0') || 0;
                const due = parseFloat(trigger.getAttribute('data-contract-due') || '0') || 0;
                const paid = parseFloat(trigger.getAttribute('data-contract-paid') || '0') || 0;

                contractIdInput.value = contractId;
                contractNumberEl.textContent = contractNumber;
                dueEl.textContent = due.toFixed(2);
                paidEl.textContent = paid.toFixed(2);
                remainingEl.textContent = remaining.toFixed(2);

                if (amountInput) {
                    const normalized = remaining > 0 ? remaining : 0;
                    const normalizedText = normalized.toFixed(2);
                    amountInput.value = normalizedText;
                    amountInput.setAttribute('max', normalizedText);
                    if (amountMaxLabel) {
                        amountMaxLabel.textContent = normalizedText;
                    }
                }

                if (accountPicker) {
                    accountPicker.value = '';
                }
                window.syncAccountHiddenGeneric('customer_account_picker_pay', 'customer_bank_account_id_pay', 'customer_safe_id_pay');

                if (dateInput) {
                    const defaultDate = dateInput.getAttribute('data-default-date') || '';
                    if (dateInput._flatpickr) {
                        dateInput._flatpickr.setDate(defaultDate || '{{ $defaultPayDate }}', true);
                    } else {
                        dateInput.value = defaultDate;
                    }
                }
            });

            if (window.flatpickr) {
                window.flatpickr('#customer_pay_date', {
                    dateFormat: 'Y-m-d',
                    locale: 'ar',
                    defaultDate: '{{ $defaultPayDate }}'
                });
            }

            if (accountPicker) {
                accountPicker.addEventListener('change', function () {
                    window.syncAccountHiddenGeneric('customer_account_picker_pay', 'customer_bank_account_id_pay', 'customer_safe_id_pay');
                });
            }

            const payForm = document.getElementById('customerContractPayForm');
            if (payForm) {
                payForm.addEventListener('submit', function (event) {
                    event.preventDefault();

                    window.syncAccountHiddenGeneric('customer_account_picker_pay', 'customer_bank_account_id_pay', 'customer_safe_id_pay');

                    const formData = new FormData(payForm);
                    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
                    const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

                    fetch(payForm.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: formData,
                    })
                        .then((response) => response.json())
                        .then((data) => {
                            if (data && data.success) {
                                window.location.reload();
                            } else {
                                alert((data && data.message) || 'حدث خطأ أثناء السداد');
                            }
                        })
                        .catch((error) => {
                            console.error(error);
                            alert('تعذر الاتصال بالخادم');
                        });

                    if (window.bootstrap && payModalEl) {
                        const modalInstance = window.bootstrap.Modal.getInstance(payModalEl);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                    }
                });
            }
        });
    </script>

    {{-- ====== Basic data ====== --}}
    <div class="card shadow-sm mb-3 kpi-card">
        <div class="card-header bg-white fw-bold">{{ __('Basic Data') }}</div>
        <div class="card-body pt-2">
            <div class="row g-3">

                <div class="col-md-6">
                    <div class="row">
                        <div class="col-5 label-col">{{ __('Name') }}</div>
                        <div class="col-7 value-col">{{ $customer->name }}</div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-5 label-col">{{ __('National ID') }}</div>
                        <div class="col-7 value-col">
                            @if($customer->national_id)
                                <span>{{ $customer->national_id }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-5 label-col">{{ __('Nationality') }}</div>
                        <div class="col-7 value-col">{{ optional($customer->nationality)->name ?? '—' }}</div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-5 label-col">{{ __('Title') }}</div>
                        <div class="col-7 value-col">{{ optional($customer->title)->name ?? '—' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="row">
                        <div class="col-5 label-col">{{ __('Phone') }}</div>
                        <div class="col-7 value-col">
                            @if($customer->phone)
                                <a href="tel:{{ $customer->phone }}" class="text-decoration-none text-dark"><i class="bi bi-telephone me-1"></i>{{ $customer->phone }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-5 label-col">{{ __('Email') }}</div>
                        <div class="col-7 value-col">
                            @if($customer->email)
                                <a href="mailto:{{ $customer->email }}" class="text-decoration-none text-dark"><i class="bi bi-envelope me-1"></i>{{ $customer->email }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-5 label-col">{{ __('Address') }}</div>
                        <div class="col-7 value-col">{{ $customer->address ?? '—' }}</div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ====== ID card image & notes ====== --}}
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100 kpi-card">
                <div class="card-header bg-white fw-bold">{{ __('ID Card Image') }}</div>
                <div class="card-body">
                    @if($customer->id_card_image)
                        <a href="{{ asset('storage/'.$customer->id_card_image) }}" target="_blank" title="{{ __('View in full size') }}">
                            <img class="img-thumb d-block mx-auto" src="{{ asset('storage/'.$customer->id_card_image) }}" alt="{{ __('ID Card Image') }}" loading="lazy">
                        </a>
                        <div class="small text-muted mt-2">{{ __('Click to open image in new window') }}</div>
                    @else
                        <div class="text-muted">{{ __('No ID card image uploaded.') }}</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100 kpi-card">
                <div class="card-header bg-white fw-bold">{{ __('Notes') }}</div>
                <div class="card-body">
                    <div class="text-wrap" style="white-space: pre-line;">
                        {{ $customer->notes ?? '—' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>

// Hide any alert automatically
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
        alert.style.transition = 'opacity .5s ease';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);
</script>
@endpush
