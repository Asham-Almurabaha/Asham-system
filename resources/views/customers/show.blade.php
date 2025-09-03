@extends('layouts.master')

@section('title', __('View Customer Data'))

@section('content')
<div class="container py-3" dir="rtl">

    {{-- Bootstrap Icons (If not added in the layout) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    @push('styles')
      <style>
        .profile-hero { background: #fff; border: 1px solid #ebeef4; border-radius: 10px; padding: 12px 16px; box-shadow: 0 1px 8px rgba(1,41,112,.06); }
        .profile-hero .avatar { width: 56px; height: 56px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: #e9ecef; color: #012970; font-weight: 700; font-size: 22px; }
        .chip { display: inline-flex; align-items: center; gap: 6px; padding: 3px 8px; border-radius: 999px; background: #f6f9ff; border: 1px solid #eaedf1; color: #012970; }
        .kpi-card { background: #fff; border: 1px solid #ebeef4; border-radius: 10px; box-shadow: 0 1px 8px rgba(1,41,112,.06); }
        .label-col { color: #6b7280; font-weight: 600; }
        .value-col { font-weight: 600; color: #111827; }
        .img-thumb { display:block; margin: .75rem auto 0; width: 100%; max-width: 320px; max-height: 280px; object-fit: cover; border-radius: 8px; box-shadow: 0 1px 8px rgba(1,41,112,.08); }
        .table thead th { position: sticky; top: 0; z-index: 1; background: #f8fafc; }
      </style>
    @endpush

    @php
        /**
         * The values coming from the controller:
         * - $contractsSummary: ['total','active','finished','other','pct_active','pct_finished','pct_other']
         * - $statusesBreakdown: [['id'=>?, 'name'=>'...', 'count'=>n, 'total_value_sum'=>.., 'formatted'=>..], ...]
         * - $installments: App\DTO\CustomerDetails\InstallmentsSummary (object)
         */

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
    @endphp

    

    {{-- ====== HERO ====== --}}
    <div class="profile-hero mb-3">
        <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar">
                    {{ mb_strtoupper(mb_substr($customer->name ?? '؟', 0, 1)) }}
                </div>
                <div>
                    <h3 class="mb-0 fw-bold fs-2 text-dark hover-primary">{{ $customer->name }}</h3>
                    <div class="small text-muted-2 mt-1">
                        <span class="chip me-1"><i class="bi bi-badge-ad"></i> {{ optional($customer->title)->name ?? '—' }}</span>
                        <span class="chip me-1"><i class="bi bi-flag"></i> {{ optional($customer->nationality)->name ?? '—' }}</span>
                        <span class="chip"><i class="bi bi-hash"></i> {{ __('ID') }}: {{ $customer->id }}</span>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-primary">
                    <i class="bi bi-pencil-square me-1"></i> {{ __('Edit') }}
                </a>
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-right-circle me-1"></i> {{ __('Back to List') }}
                </a>
            </div>
        </div>
    </div>

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
                <div class="small text-muted">{{ __('All contracts associated with the customer') }}</div>
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
                                $name = (string)($st['name'] ?? 'Undefined');
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
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:140px">{{ __('Contract Number') }}</th>
                                <th style="width:120px">{{ __('Start Date') }}</th>
                                <th>{{ __('Product Type') }}</th>
                                <th class="text-end" style="width:140px">{{ __('Total Due') }}</th>
                                <th class="text-end" style="width:140px">{{ __('Paid') }}</th>
                                <th class="text-end" style="width:140px">{{ __('Remaining') }}</th>
                            </tr>
                        </thead>
                        <tbody>
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
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="3" class="text-end">{{ __('Total') }}</th>
                                <th class="text-end">{{ $nf($totDue) }}</th>
                                <th class="text-end text-success">{{ $nf($totPaid) }}</th>
                                <th class="text-end {{ $totRemain>0 ? 'text-danger' : 'text-muted' }}">{{ $nf($totRemain) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="p-3 text-muted">{{ __('No active contracts to display.') }}</div>
            @endif
        </div>
    </div>
    {{-- ====== End of active contracts table ====== --}}

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
                                <button class="btn btn-light btn-sm ms-1" onclick="copyText('{{ $customer->national_id }}')" title="{{ __('Copy') }}">
                                    <i class="bi bi-clipboard"></i>
                                </button>
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
                                <button class="btn btn-light btn-sm ms-1" onclick="copyText('{{ $customer->phone }}')" title="{{ __('Copy') }}">
                                    <i class="bi bi-clipboard"></i>
                                </button>
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
function copyText(txt){
    navigator.clipboard?.writeText(txt).then(() => {
        const el = document.createElement('div');
        el.textContent = '{{ __('Copied') }}';
        el.style.position = 'fixed';
        el.style.bottom = '16px';
        el.style.left = '50%';
        el.style.transform = 'translateX(-50%)';
        el.style.background = 'rgba(0,0,0,.8)';
        el.style.color = '#fff';
        el.style.padding = '6px 12px';
        el.style.borderRadius = '999px';
        el.style.fontSize = '12px';
        el.style.zIndex = 9999;
        document.body.appendChild(el);
        setTimeout(()=>{ el.remove(); }, 900);
    });
}

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

