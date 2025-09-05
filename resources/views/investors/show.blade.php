@extends('layouts.master')

@section('title', 'عرض بيانات المستثمر')

@section('content')
<div class="container py-3" dir="rtl">

    {{-- Bootstrap Icons (لو مش مضافة في الـ layout) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    @php
        // ====== Fallbacks آمنة ======
        $currencySymbol    = $currencySymbol    ?? 'ر.س';

        $contractsTotal    = (int)($contractsTotal  ?? 0);
        $contractsEnded    = (int)($contractsEnded  ?? 0);
        $contractsActive   = (int)($contractsActive ?? max($contractsTotal - $contractsEnded, 0));

        $activePct         = isset($activePct) ? (float)$activePct : ($contractsTotal ? round($contractsActive/$contractsTotal*100,1) : 0);
        $endedPct          = isset($endedPct)  ? (float)$endedPct  : ($contractsTotal ? round($contractsEnded/$contractsTotal*100,1)  : 0);

        // مجاميع "نشِط"
        $totalCapitalShare = (float)($totalCapitalShare ?? 0);
        $totalProfitGross  = (float)($totalProfitGross  ?? 0);
        $totalOfficeCut    = (float)($totalOfficeCut    ?? 0);
        $totalProfitNet    = (float)($totalProfitNet    ?? ($totalProfitGross - $totalOfficeCut));

        // نصيب المستثمر من مدفوعات العميل تناسبياً (Pro-Rata)
        $totalPaidPortionToInvestor = (float)($totalPaidPortionToInvestor ?? 0);

        // المتبقي على العملاء لصالح المستثمر
        $totalRemainingOnCustomers  = (float)($totalRemainingOnCustomers  ?? max(($totalCapitalShare + $totalProfitNet) - $totalPaidPortionToInvestor, 0));

        // مجاميع "كل العقود" (نشِط + منتهي)
        $totalCapitalShareAll = (float)($totalCapitalShareAll ?? 0);
        $totalProfitGrossAll  = (float)($totalProfitGrossAll  ?? 0);
        $totalOfficeCutAll    = (float)($totalOfficeCutAll    ?? 0);
        $totalProfitNetAll    = (float)($totalProfitNetAll    ?? ($totalProfitGrossAll - $totalOfficeCutAll));

        $contractBreakdown = $contractBreakdown ?? [];
        $liquidity         = isset($liquidity) ? (float)$liquidity : 0.0;

        // ====== ملخص الأقساط الشهري لهذا المستثمر ======
        $monthly   = (array)($installmentsMonthly ?? []);
        $totals    = (array)($monthly['totals'] ?? []);
        $dueSum    = (float)($totals['due'] ?? 0);
        $paidSum   = (float)($totals['paid'] ?? 0);
        $remainSum = (float)($totals['remaining'] ?? max($dueSum - $paidSum, 0));
        $dueCount  = (int)  ($totals['count'] ?? 0);
        $paidPct2  = $dueSum > 0 ? round(($paidSum / $dueSum) * 100, 1) : 0;

        $monthLabel       = (string)($monthly['month_label'] ?? now()->format('Y-m'));
        $excludedStatuses = (array)($monthly['excluded_status_names'] ?? ['مؤجل','معتذر']);
        $excludedStatusesTx = count($excludedStatuses) ? implode('، ', $excludedStatuses) : '—';

        $mVal = (int)($monthly['month'] ?? now()->month);
        $yVal = (int)($monthly['year']  ?? now()->year);

        // لعرض الصور داخل التفاصيل
        $hasIdCard   = !empty($investor->id_card_image);
        $hasContract = !empty($investor->contract_image);
    @endphp

    

    {{-- ====== HERO ====== --}}
    <div class="profile-hero mb-3">
        <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar">
                    {{ mb_strtoupper(mb_substr($investor->name ?? '؟', 0, 1)) }}
                </div>
                <div>
                    <h3 class="mb-0 fw-bold fs-2 text-dark hover-primary">{{ $investor->name }}</h3>
                    <div class="small text-muted-2 mt-1 d-flex flex-wrap gap-1">
                        <span class="chip"><i class="bi bi-badge-ad me-1"></i>{{ optional($investor->title)->name ?? '—' }}</span>
                        <span class="chip"><i class="bi bi-flag me-1"></i>{{ optional($investor->nationality)->name ?? '—' }}</span>
                        <span class="chip"><i class="bi bi-hash me-1"></i>{{ __('ID:') }} {{ $investor->id }}</span>
                    </div>
                </div>
            </div>
            <div class="mini-actions d-flex flex-wrap gap-2">
                {{-- <a href="{{ route('investors.edit', $investor) }}" class="btn btn-primary">
                    <i class="bi bi-pencil-square me-1"></i> تعديل
                </a> --}}
                
                 {{-- ✅ Dropdown للتقارير --}}
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-dark dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        📊 التقارير
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end text-end">
                        <li>
                            <a class="dropdown-item" href="{{ route('investors.statement.statement', $investor) }}">
                                📄 تقرير جرد المستثمر
                            </a>
                        </li>
                        
                        <li>
                            <a class="dropdown-item" href="{{ route('investors.deposits.deposits', $investor) }}">
                                💰 جرد الإيداعات
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('investors.withdrawals.withdrawals', $investor) }}">
                                💸 جرد المسحوبات
                            </a>
                        </li>
                        
                        <li>
                            <a class="dropdown-item" href="{{ route('investors.transactions.transactions', $investor) }}">
                                🔄 جرد إيداعات / مسحوبات
                            </a>
                        </li>
                    </ul>
                </div>

                <a href="{{ route('investors.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-right-circle me-1"></i> العودة للقائمة
                </a>

            </div>
        </div>
    </div>


    {{-- ====== ملخص أقساط هذا الشهر (لـ {{ $investor->name }}) ====== --}}
    <div class="card shadow-soft mb-4">
        <div class="card-header bg-white d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="section-title">@lang('Monthly Installments Summary') <span class="text-muted">({{ $monthLabel }})</span></div>
                <span class="stat-sub"><i class="bi bi-filter"></i> @lang('Excludes statuses:') {{ $excludedStatusesTx }}</span>
            </div>
            {{-- اختيار سريع للشهر/السنة (يحافظ على الـquerystring) --}}
            <form action="{{ route('investors.show', $investor) }}" method="GET" class="d-flex align-items-center gap-2">
                @foreach(request()->except(['m','y','page']) as $k => $v)
                    @if(is_array($v))
                        @foreach($v as $vv)
                            <input type="hidden" name="{{ $k }}[]" value="{{ $vv }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endif
                @endforeach
                <input type="number" name="m" min="1" max="12" class="form-control form-control-sm" style="width:86px" value="{{ request('m', $mVal) }}" placeholder="شهر">
                <input type="number" name="y" min="2000" max="2100" class="form-control form-control-sm" style="width:92px" value="{{ request('y', $yVal) }}" placeholder="سنة">
                <button class="btn btn-outline-primary btn-sm">تحديث</button>
            </form>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-3">
                    <div class="kpi-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="kpi-icon"><i class="bi bi-journal-check fs-4 text-primary"></i></div>
                            <div>
                                <div class="stat-sub">@lang('Number of Due Installments')</div>
                                <div class="fs-2 fw-bold">{{ number_format($dueCount) }}</div>
                                <div class="stat-sub">@lang('Count')</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-5">
                    <div class="kpi-card p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="kpi-icon"><i class="bi bi-cash-coin fs-4 text-success"></i></div>
                                <div>
                                    <div class="stat-sub">@lang('Unpaid Amount This Month')</div>
                                    <div class="fs-2 fw-bold">{{ number_format($dueSum, 2) }} <span class="fs-6 text-muted">{{ $currencySymbol }}</span></div>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="stat-sub">مدفوع</div>
                                <div class="fw-bold">{{ number_format($paidSum,2) }}</div>
                            </div>
                        </div>
                        <div class="progress bar-8" title="نسبة المدفوع">
                            <div class="progress-bar" style="width: {{ $paidPct2 }}%"></div>
                        </div>
                        <div class="d-flex justify-content-between stat-sub mt-1">
                            <span>@lang('Paid Percentage'): {{ number_format($paidPct2,1) }}%</span>
                            <span>@lang('Remaining:') {{ number_format($remainSum,2) }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="kpi-card p-3 h-100">
                        <div class="d-flex align-items-center gap-3">
                            <div class="kpi-icon"><i class="bi bi-wallet2 fs-4 text-warning"></i></div>
                            <div>
                                <div class="stat-sub">@lang('Remaining to Pay')</div>
                                <div class="fs-2 fw-bold">{{ number_format($remainSum, 2) }} <span class="fs-6 text-muted">{{ $currencySymbol }}</span></div>
                                <div class="stat-sub">ضمن الفترة المحددة</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ====== KPIs العقود الأساسية ====== --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-files fs-5 text-primary"></i></div>
                    <div class="fw-bold text-muted">إجمالي العقود المشاركة</div>
                </div>
                <div class="fs-2 fw-bold">{{ number_format($contractsTotal) }}</div>
                <div class="stat-sub">جميع العقود المرتبطة بالمستثمر</div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-person-check fs-5 text-success"></i></div>
                    <div class="fw-bold text-muted">العقود النشطة</div>
                </div>
                <div class="fs-2 fw-bold text-pos">{{ number_format($contractsActive) }}</div>
                <div class="stat-sub">النسبة: {{ number_format($activePct,1) }}%</div>
                <div class="progress bar-8 mt-2"><div class="progress-bar" style="width: {{ $activePct }}%"></div></div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-archive fs-5 text-danger"></i></div>
                    <div class="fw-bold text-muted">العقود المنتهية</div>
                </div>
                <div class="fs-2 fw-bold text-neg">{{ number_format($contractsEnded) }}</div>
                <div class="stat-sub">النسبة: {{ number_format($endedPct,1) }}%</div>
                <div class="progress bar-8 mt-2"><div class="progress-bar bg-danger" style="width: {{ $endedPct }}%"></div></div>
            </div>
        </div>
    </div>

    {{-- ====== كروت "المتبقي على العملاء" + "سيولة المستثمر" ====== --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-cash-coin fs-5 text-success"></i></div>
                    <div class="fw-bold text-muted">سيولة المستثمر</div>
                </div>
                <div class="fs-2 fw-bold {{ $liquidity >= 0 ? 'text-pos' : 'text-neg' }}">
                    {{ number_format($liquidity, 2) }} <span class="fs-6 text-muted">{{ $currencySymbol }}</span>
                </div>
                <div class="stat-sub">{{ $liquidity >= 0 ? 'صافي الرصيد المتاح' : 'صافي الرصيد المستحق' }}</div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-cash-stack fs-5 text-warning"></i></div>
                    <div class="fw-bold text-muted">المتبقي على العملاء</div>
                </div>
                <div class="fs-2 fw-bold {{ $totalRemainingOnCustomers >= 0 ? 'text-pos' : 'text-neg' }}">
                    {{ number_format($totalRemainingOnCustomers, 2) }} <span class="fs-6 text-muted">{{ $currencySymbol }}</span>
                </div>
                <div class="stat-sub">
                    = رأس المال + (ربح المستثمر − نصيب المكتب) −
                    <span title="نصيب المستثمر من مدفوعات العميل تناسبياً">المدفوع</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ====== إجماليات كل العقود (نشِط + منتهي) ====== --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-3">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="kpi-icon"><i class="bi bi-wallet2 fs-5 text-primary"></i></div>
                    <div class="fw-bold text-muted">حصة رأس المال (كل العقود)</div>
                </div>
                <div class="fs-2 fw-bold">
                    {{ number_format($totalCapitalShareAll, 2) }} <span class="fs-6 text-muted">{{ $currencySymbol }}</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="kpi-icon"><i class="bi bi-graph-up fs-5 text-success"></i></div>
                    <div class="fw-bold text-muted">ربح المستثمر (كل العقود)</div>
                </div>
                <div class="fs-2 fw-bold">
                    {{ number_format($totalProfitGrossAll, 2) }} <span class="fs-6 text-muted">{{ $currencySymbol }}</span>
                </div>
                <div class="stat-sub">قبل خصم نسبة المكتب</div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="kpi-icon"><i class="bi bi-building fs-5 text-danger"></i></div>
                    <div class="fw-bold text-muted">نصيب المكتب (كل العقود)</div>
                </div>
                <div class="fs-2 fw-bold text-neg">
                    {{ number_format($totalOfficeCutAll, 2) }} <span class="fs-6 text-muted">{{ $currencySymbol }}</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="kpi-icon"><i class="bi bi-cash fs-5 text-warning"></i></div>
                    <div class="fw-bold text-muted">ربح المستثمر (كل العقود)</div>
                </div>
                <div class="fs-2 fw-bold">
                    {{ number_format($totalProfitNetAll, 2) }} <span class="fs-6 text-muted">{{ $currencySymbol }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ====== جدول تفصيلي للعقود النشطة ====== --}}
    @if(!empty($contractBreakdown))
        <div class="card border-0 shadow-soft">
            <div class="card-header bg-white fw-bold">تفصيل العقود النشطة</div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-center mb-0">
                    <thead class="table-light position-sticky top-0" style="z-index:1;">
                        <tr>
                            <th style="width:60px">#</th>
                            <th>رقم/مُعرّف العقد</th>
                            <th>العميل</th>
                            <th>النسبة %</th>
                            <th>رأس المال</th>
                            <th>ربح المستثمر (إجمالي)</th>
                            <th>نصيب المكتب</th>
                            <th>الربح الصافي</th>
                            <th title="نصيب المستثمر من مدفوعات العميل تناسبياً">المدفوع</th>
                            <th>المتبقي على العملاء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contractBreakdown as $i => $r)
                            <tr>
                                <td class="text-muted">{{ $i+1 }}</td>
                                <td>
                                    @php
                                        $cid = $r['contract_id'] ?? null;
                                        $cno = $r['contract_number'] ?? null;
                                    @endphp
                                    @if(!empty($cid))
                                        <a href="{{ route('contracts.show', $cid) }}" class="text-decoration-none text-dark hover-primary fw-bold">
                                            {{ $cno ?: ('#'.$cid) }}
                                        </a>
                                    @else
                                        {{ $cno ?: '?' }}
                                    @endif
                                </td>
                                <td class="text-start">
                                    @php $custId = $r['customer_id'] ?? null; @endphp
                                    @if(!empty($custId))
                                        <a href="{{ route('customers.show', $custId) }}" class="text-decoration-none text-dark hover-primary">
                                            {{ $r['customer'] ?? '?' }}
                                        </a>
                                    @else
                                        {{ $r['customer'] ?? '?' }}
                                    @endif
                                </td>
                                <td dir="ltr">{{ number_format($r['share_pct'],2) }}</td>
                                <td dir="ltr">{{ number_format($r['share_value'],2) }}</td>
                                <td dir="ltr">{{ number_format($r['profit_gross'],2) }}</td>
                                <td class="text-neg" dir="ltr">{{ number_format($r['office_cut'],2) }}</td>
                                <td dir="ltr">{{ number_format($r['profit_net'],2) }}</td>
                                <td dir="ltr">{{ number_format($r['paid_to_investor_from_customer'] ?? 0,2) }}</td>
                                <td class="fw-semibold {{ ($r['remaining_on_customers'] ?? 0) >= 0 ? 'text-pos' : 'text-neg' }}" dir="ltr">
                                    {{ number_format($r['remaining_on_customers'] ?? 0,2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="4" class="text-end">الإجماليات:</th>
                            <th dir="ltr">{{ number_format($totalCapitalShare,2) }}</th>
                            <th dir="ltr">{{ number_format($totalProfitGross,2) }}</th>
                            <th class="text-neg" dir="ltr">{{ number_format($totalOfficeCut,2) }}</th>
                            <th dir="ltr">{{ number_format($totalProfitNet,2) }}</th>
                            <th dir="ltr">{{ number_format($totalPaidPortionToInvestor,2) }}</th>
                            <th class="fw-bold {{ $totalRemainingOnCustomers >= 0 ? 'text-pos' : 'text-neg' }}" dir="ltr">
                                {{ number_format($totalRemainingOnCustomers,2) }}
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

    {{-- ====== تفاصيل المستثمر (مع الصور داخل التفاصيل) ====== --}}
    <div class="card shadow-sm mb-3 kpi-card">
        <div class="card-header bg-white fw-bold">بيانات أساسية</div>
        <div class="card-body pt-2">
            <div class="row g-3">

                <div class="col-md-6">
                    <div class="row"><div class="col-5 label-col">الاسم</div><div class="col-7 value-col">{{ $investor->name }}</div></div>
                    <div class="row mt-2">
                        <div class="col-5 label-col">رقم الهوية</div>
                        <div class="col-7 value-col">
                            @if($investor->national_id)
                                <span dir="ltr">{{ $investor->national_id }}</span>
                                <button class="btn btn-light btn-sm ms-1" onclick="copyText('{{ $investor->national_id }}')" title="نسخ"><i class="bi bi-clipboard"></i></button>
                            @else <span class="text-muted">—</span> @endif
                        </div>
                    </div>
                    <div class="row mt-2"><div class="col-5 label-col">الجنسية</div><div class="col-7 value-col">{{ optional($investor->nationality)->name ?? '—' }}</div></div>
                    <div class="row mt-2"><div class="col-5 label-col">الوظيفة</div><div class="col-7 value-col">{{ optional($investor->title)->name ?? '—' }}</div></div>
                </div>
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-5 label-col">الهاتف</div>
                        <div class="col-7 value-col">
                            @if($investor->phone)
                                <a href="tel:{{ $investor->phone }}" class="text-decoration-none text-dark"><i class="bi bi-telephone me-1"></i>{{ $investor->phone }}</a>
                                <button class="btn btn-light btn-sm ms-1" onclick="copyText('{{ $investor->phone }}')" title="نسخ"><i class="bi bi-clipboard"></i></button>
                            @else <span class="text-muted">—</span> @endif
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-5 label-col">البريد الإلكتروني</div>
                        <div class="col-7 value-col">
                            @if($investor->email) <a href="mailto:{{ $investor->email }}" class="text-decoration-none text-dark"><i class="bi bi-envelope me-1"></i>{{ $investor->email }}</a>
                            @else <span class="text-muted">—</span> @endif
                        </div>
                    </div>
                    <div class="row mt-2"><div class="col-5 label-col">العنوان</div><div class="col-7 value-col">{{ $investor->address ?? '—' }}</div></div>
                </div>
            </div>

            {{-- الصور داخل التفاصيل --}}
            <div class="row g-3 mt-2">
                <div class="col-12 col-md-6">
                    <div class="row align-items-start">
                        <div class="col-5 label-col">صورة الهوية</div>
                        <div class="col-7 value-col">
                            @if($hasIdCard)
                                <a href="{{ asset('storage/'.$investor->id_card_image) }}" target="_blank" title="عرض بالحجم الكامل">
                                    <img class="img-thumb" src="{{ asset('storage/'.$investor->id_card_image) }}" alt="صورة الهوية">
                                </a>
                                <div class="small text-muted mt-1">انقر لفتح الصورة في نافذة جديدة</div>
                            @else
                                <span class="text-muted">لا توجد صورة هوية مرفوعة.</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="row align-items-start">
                        <div class="col-5 label-col">صورة العقد</div>
                        <div class="col-7 value-col">
                            @if($hasContract)
                                <a href="{{ asset('storage/'.$investor->contract_image) }}" target="_blank" title="عرض بالحجم الكامل">
                                    <img class="img-thumb" src="{{ asset('storage/'.$investor->contract_image) }}" alt="صورة العقد">
                                </a>
                                <div class="small text-muted mt-1">انقر لفتح الصورة في نافذة جديدة</div>
                            @else
                                <span class="text-muted">لا توجد صورة عقد مرفوعة.</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            {{-- /الصور داخل التفاصيل --}}
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

// تفعيل Bootstrap Tooltip لو موجود
document.addEventListener('DOMContentLoaded', function () {
    if (window.bootstrap && bootstrap.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el, {container: 'body'});
        });
    }
});

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
