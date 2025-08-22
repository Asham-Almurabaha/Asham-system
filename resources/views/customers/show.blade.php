@extends('layouts.master')

@section('title', 'عرض بيانات العميل')

@section('content')
<div class="container py-3" dir="rtl">

    {{-- Bootstrap Icons (لو مش مضافة في الـ layout) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    @php
        /**
         * القيم القادمة من الكنترولر:
         * - $contractsSummary: ['total','active','finished','other','pct_active','pct_finished','pct_other']
         * - $statusesBreakdown: [['id'=>?, 'name'=>'...', 'count'=>n, 'total_value_sum'=>.., 'formatted'=>..], ...]
         * - $installments: App\DTO\CustomerDetails\InstallmentsSummary (object)
         */

        // ====== تلخيص سريع من بيانات الخدمة ======
        $cs = $contractsSummary ?? ['total'=>0,'active'=>0,'finished'=>0,'other'=>0];
        $contractsCount       = (int)($cs['total']    ?? 0);
        $activeContractsCount = (int)($cs['active']   ?? 0);

        $pct = fn($k) => isset($cs["pct_$k"]) ? (float)$cs["pct_$k"] : ($cs['total']>0 ? round(($cs[$k] ?? 0)/$cs['total']*100, 1) : 0);
        $activePct   = $pct('active');
        $finishedPct = $pct('finished');
        $otherPct    = $pct('other');

        // توزيع حالات العقود
        $sb = collect($statusesBreakdown ?? [])->values();
        $sb_total = max(1, (int) ($sb->sum('count') ?: $contractsCount)); // لتجنّب القسمة على صفر

        // ملخص الأقساط (object أو array احتياطاً)
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

    <style>
        :root{
            --card-r: 1rem;
            --soft: 0 8px 20px rgba(0,0,0,.06);
            --soft-2: 0 10px 26px rgba(0,0,0,.08);
        }
        .profile-hero{
            border:1px solid #eef2f7; border-radius: var(--card-r);
            background: linear-gradient(135deg,#f7fbff 0%,#ffffff 70%);
            padding: 1.25rem 1rem; box-shadow: var(--soft);
        }
        .avatar{
            width:64px; height:64px; border-radius:50%;
            display:grid; place-items:center;
            background:#e8f0fe; color:#1e40af; font-weight:800; font-size:1.25rem;
        }
        .kpi-card{
            border:1px solid #eef2f7; border-radius: var(--card-r);
            box-shadow: var(--soft); transition:.2s; height:100%;
        }
        .kpi-card:hover{ box-shadow: var(--soft-2); transform: translateY(-2px); }
        .kpi-icon{
            width:48px;height:48px;border-radius:.85rem;display:grid;place-items:center;background:#f4f6fb;
        }
        .chip{ background:#f1f4f9; color:#3c4a5d; border-radius:999px; padding:.35rem .6rem; font-weight:600; }
        .label-col{ color:#6b7280; font-weight:600; }
        .value-col{ font-weight:600; }
        .text-pos{ color:#16a34a !important; }
        .text-muted-2{ color:#6b7280 !important; }
        .img-thumb{ max-width: 160px; max-height: 120px; object-fit: cover; border-radius:.5rem; border:1px solid #eef2f7; }
    </style>

    {{-- ====== HERO ====== --}}
    <div class="profile-hero mb-3">
        <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar">
                    {{ mb_strtoupper(mb_substr($customer->name ?? '؟', 0, 1)) }}
                </div>
                <div>
                    <h3 class="mb-0">{{ $customer->name }}</h3>
                    <div class="small text-muted-2 mt-1">
                        <span class="chip me-1"><i class="bi bi-badge-ad"></i> {{ optional($customer->title)->name ?? '—' }}</span>
                        <span class="chip me-1"><i class="bi bi-flag"></i> {{ optional($customer->nationality)->name ?? '—' }}</span>
                        <span class="chip"><i class="bi bi-hash"></i> ID: {{ $customer->id }}</span>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-primary">
                    <i class="bi bi-pencil-square me-1"></i> تعديل
                </a>
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-right-circle me-1"></i> العودة للقائمة
                </a>
            </div>
        </div>
    </div>

    {{-- ====== كروت العقود والأقساط (من خدمة تفاصيل العميل) ====== --}}
    @php
        $cs_active   = (int)($cs['active']   ?? 0);
        $cs_finished = (int)($cs['finished'] ?? 0);
        $cs_other    = (int)($cs['other']    ?? 0);
    @endphp

    {{-- صف ملخص العقود --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-files fs-5 text-primary"></i></div>
                    <div class="fw-bold text-muted">إجمالي العقود</div>
                </div>
                <div class="fs-2 fw-bold">{{ number_format($contractsCount) }}</div>
                <div class="small text-muted">كل العقود المرتبطة بالعميل</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-person-check fs-5 text-success"></i></div>
                    <div class="fw-bold text-muted">عقود نشطة</div>
                </div>
                <div class="fs-2 fw-bold text-success">{{ number_format($cs_active) }}</div>
                <div class="small text-muted">النسبة: {{ number_format($activePct,1) }}%</div>
                <div class="progress mt-2" style="height:8px;">
                    <div class="progress-bar" role="progressbar" style="width: {{ $activePct }}%"></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-flag-fill fs-5 text-secondary"></i></div>
                    <div class="fw-bold text-muted">عقود منتهية</div>
                </div>
                <div class="fs-2 fw-bold">{{ number_format($cs_finished) }}</div>
                <div class="small text-muted">النسبة: {{ number_format($finishedPct,1) }}%</div>
                <div class="progress mt-2" style="height:8px;">
                    <div class="progress-bar bg-secondary" role="progressbar" style="width: {{ $finishedPct }}%"></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-three-dots fs-5 text-warning"></i></div>
                    <div class="fw-bold text-muted">أخرى</div>
                </div>
                <div class="fs-2 fw-bold">{{ number_format($cs_other) }}</div>
                <div class="small text-muted">النسبة: {{ number_format($otherPct,1) }}%</div>
                <div class="progress mt-2" style="height:8px;">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $otherPct }}%"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- صف: ملخص الأقساط + توزيع الحالات --}}
    <div class="row g-3 mb-3">
        {{-- ملخص الأقساط --}}
        <div class="col-12 col-xl-8">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="kpi-icon"><i class="bi bi-cash-coin fs-5 text-primary"></i></div>
                        <div class="fw-bold text-muted">ملخص الأقساط</div>
                    </div>
                    <span class="badge text-bg-light">
                        إجمالي الأقساط: {{ number_format($i_total_installments) }} /
                        إجمالي مستحق: {{ $nf($i_due_amount) }}
                    </span>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="small text-muted mb-1">مدفوع</div>
                            <div class="fw-bold text-success">{{ $nf($i_paid_amount) }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="small text-muted mb-1">غير مدفوع</div>
                            <div class="fw-bold">{{ $nf($i_unpaid_amount) }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="small text-muted mb-1">متأخر</div>
                            <div class="fw-bold text-danger">
                                {{ number_format($i_overdue_count) }} قسط — {{ $nf($i_overdue_amount) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-12 col-md-6">
                        <div class="border rounded p-2 h-100">
                            <div class="small text-muted mb-1">القسط القادم</div>
                            <div class="fw-bold">{{ $next_due_date ? \Carbon\Carbon::parse($next_due_date)->format('Y-m-d') : '—' }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="border rounded p-2 h-100">
                            <div class="small text-muted mb-1">آخر سداد</div>
                            <div class="fw-bold">{{ $last_payment_date ? \Carbon\Carbon::parse($last_payment_date)->format('Y-m-d') : '—' }}</div>
                        </div>
                    </div>
                </div>

                @php
                    // نسب مدفوع/غير مدفوع من إجمالي المستحق
                    $paidPct   = $i_due_amount > 0 ? round(($i_paid_amount / $i_due_amount) * 100) : 0;
                    $unpaidPct = $i_due_amount > 0 ? round(($i_unpaid_amount / $i_due_amount) * 100) : 0;
                @endphp
                <div class="mt-3">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>نسبة المدفوع من إجمالي المستحق</span>
                        <span>{{ $paidPct }}%</span>
                    </div>
                    <div class="progress" style="height:8px;">
                        <div class="progress-bar bg-success" style="width: {{ $paidPct }}%"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mt-2 mb-1">
                        <span>نسبة غير المدفوع من إجمالي المستحق</span>
                        <span>{{ $unpaidPct }}%</span>
                    </div>
                    <div class="progress" style="height:8px;">
                        <div class="progress-bar bg-secondary" style="width: {{ $unpaidPct }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- توزيع حالات العقود --}}
        <div class="col-12 col-xl-4">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-pie-chart fs-5 text-warning"></i></div>
                    <div class="fw-bold text-muted">توزيع حالات العقود</div>
                </div>

                @if($sb->isNotEmpty())
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($sb as $st)
                            @php
                                $cnt  = (int)($st['count'] ?? 0);
                                $name = (string)($st['name'] ?? 'غير مُعرّف');
                                $pct  = $cnt>0 ? round(($cnt / $sb_total) * 100) : 0;
                            @endphp
                            <span class="badge text-bg-light">
                                {{ $name }} — {{ number_format($cnt) }} ({{ $pct }}%)
                            </span>
                        @endforeach
                    </div>
                @else
                    <div class="text-muted">لا توجد بيانات كافية لعرض التوزيع.</div>
                @endif
            </div>
        </div>
    </div>
    {{-- ====== نهاية كروت العقود والأقساط ====== --}}

    {{-- ====== جدول العقود النشطة: المدفوع والمتبقي ====== --}}
    @php
        // نحاول جلب قائمة العقود النشطة من أكثر من مسار لضمان التوافق
        $activeList = collect($activeContracts ?? ($details->active ?? ($customerDetails['contracts']['active'] ?? [])))->values();

        $totDue = 0.0; $totPaid = 0.0; $totRemain = 0.0;
    @endphp

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-bold d-flex align-items-center justify-content-between">
            <span><i class="bi bi-card-checklist me-1"></i>العقود النشطة</span>
            <span class="badge text-bg-light">عدد: {{ number_format($activeList->count()) }}</span>
        </div>

        <div class="card-body p-0">
            @if($activeList->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:140px">رقم العقد</th>
                                <th style="width:120px">تاريخ البداية</th>
                                <th>نوع البضاعة</th>
                                <th class="text-end" style="width:140px">إجمالي مستحق</th>
                                <th class="text-end" style="width:140px">مدفوع</th>
                                <th class="text-end" style="width:140px">متبقي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activeList as $row)
                                @php
                                    $isObj   = is_object($row);
                                    $cno     = $isObj ? ($row->contract_number ?? '') : ($row['contract_number'] ?? '');
                                    $sdate   = $isObj ? ($row->start_date ?? null)     : ($row['start_date'] ?? null);
                                    $ptype   = $isObj
                                                ? ($row->product_type_name ?? ($row->product_type->name ?? null))
                                                : ($row['product_type_name'] ?? ($row['product_type']['name'] ?? null));

                                    // قراءة القيم سواء كانت بخصائص مباشرة أو ضمن installments[]
                                    $due     = $isObj ? ($row->due_sum ?? 0)     : ($row['due_sum'] ?? ($row['installments']['due_sum'] ?? 0));
                                    $paid    = $isObj ? ($row->paid_sum ?? 0)    : ($row['paid_sum'] ?? ($row['installments']['paid_sum'] ?? 0));
                                    $remain  = $isObj ? ($row->remaining_amount ?? $row->unpaid_sum ?? 0)
                                                      : ($row['remaining_amount'] ?? ($row['unpaid_sum'] ?? ($row['installments']['unpaid_sum'] ?? 0)));

                                    $totDue   += (float)$due;
                                    $totPaid  += (float)$paid;
                                    $totRemain+= (float)$remain;
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $cno }}</td>
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
                                <th colspan="3" class="text-end">الإجمالي</th>
                                <th class="text-end">{{ $nf($totDue) }}</th>
                                <th class="text-end text-success">{{ $nf($totPaid) }}</th>
                                <th class="text-end {{ $totRemain>0 ? 'text-danger' : 'text-muted' }}">{{ $nf($totRemain) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="p-3 text-muted">لا توجد عقود نشطة لعرضها.</div>
            @endif
        </div>
    </div>
    {{-- ====== نهاية جدول العقود النشطة ====== --}}

    {{-- ====== بيانات أساسية ====== --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-bold">بيانات أساسية</div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-6">
                    <div class="row">
                        <div class="col-5 label-col">الاسم</div>
                        <div class="col-7 value-col">{{ $customer->name }}</div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-5 label-col">رقم الهوية</div>
                        <div class="col-7 value-col">
                            @if($customer->national_id)
                                <span>{{ $customer->national_id }}</span>
                                <button class="btn btn-light btn-sm ms-1" onclick="copyText('{{ $customer->national_id }}')" title="نسخ">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-5 label-col">الجنسية</div>
                        <div class="col-7 value-col">{{ optional($customer->nationality)->name ?? '—' }}</div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-5 label-col">الوظيفة</div>
                        <div class="col-7 value-col">{{ optional($customer->title)->name ?? '—' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="row">
                        <div class="col-5 label-col">الهاتف</div>
                        <div class="col-7 value-col">
                            @if($customer->phone)
                                <a href="tel:{{ $customer->phone }}">{{ $customer->phone }}</a>
                                <button class="btn btn-light btn-sm ms-1" onclick="copyText('{{ $customer->phone }}')" title="نسخ">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-5 label-col">البريد الإلكتروني</div>
                        <div class="col-7 value-col">
                            @if($customer->email)
                                <a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-5 label-col">العنوان</div>
                        <div class="col-7 value-col">{{ $customer->address ?? '—' }}</div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ====== صورة الهوية & الملاحظات ====== --}}
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">صورة الهوية</div>
                <div class="card-body">
                    @if($customer->id_card_image)
                        <a href="{{ asset('storage/'.$customer->id_card_image) }}" target="_blank" title="عرض بالحجم الكامل">
                            <img class="img-thumb" src="{{ asset('storage/'.$customer->id_card_image) }}" alt="صورة الهوية">
                        </a>
                        <div class="small text-muted mt-2">انقر لفتح الصورة في نافذة جديدة</div>
                    @else
                        <div class="text-muted">لا توجد صورة هوية مرفوعة.</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">ملاحظات</div>
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
        el.textContent = 'تم النسخ';
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

// إخفاء أي alert تلقائياً
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
        alert.style.transition = 'opacity .5s ease';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);
</script>
@endpush
