@extends('layouts.master')

@section('title', 'عرض بيانات المستثمر')

@section('content')
<div class="container py-3" dir="rtl">

    {{-- Bootstrap Icons (لو مش مضافة في الـ layout) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    @php
        // ====== قيم العرض الأساسية ======
        $pct = (float) ($investor->office_share_percentage ?? 0);
        $pct = max(0, min(100, $pct));

        $hasIdCard   = !empty($investor->id_card_image);
        $hasContract = !empty($investor->contract_image);
        $filesCount  = ($hasIdCard ? 1 : 0) + ($hasContract ? 1 : 0);

        // ====== سيولة المستثمر ======
        $currencySymbol = 'ر.س'; // غيّرها إذا لزم
        $liquidity = 0.0;

        try {
            // (1) عمود liquidity إن وُجد
            if (\Illuminate\Support\Facades\Schema::hasColumn('investors', 'liquidity')) {
                $liquidity = (float) ($investor->liquidity ?? 0);

            // (2) أو balance
            } elseif (\Illuminate\Support\Facades\Schema::hasColumn('investors', 'balance')) {
                $liquidity = (float) ($investor->balance ?? 0);

            // (3) أو علاقة wallet->balance
            } elseif (method_exists($investor, 'wallet') && optional($investor->wallet)->balance !== null) {
                $liquidity = (float) $investor->wallet->balance;

            // (4) أو علاقة transactions (type: credit/debit, amount)
            } elseif (method_exists($investor, 'transactions')) {
                $credits = (float) $investor->transactions()->where('type','credit')->sum('amount');
                $debits  = (float) $investor->transactions()->where('type','debit')->sum('amount');
                $liquidity = $credits - $debits;
            }
        } catch (\Throwable $e) {
            $liquidity = 0.0; // fallback آمن
        }

        $liquidity = round($liquidity, 2);
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
                    {{ mb_strtoupper(mb_substr($investor->name ?? '؟', 0, 1)) }}
                </div>
                <div>
                    <h3 class="mb-0">{{ $investor->name }}</h3>
                    <div class="small text-muted-2 mt-1">
                        <span class="chip me-1"><i class="bi bi-badge-ad"></i> {{ optional($investor->title)->name ?? '—' }}</span>
                        <span class="chip me-1"><i class="bi bi-flag"></i> {{ optional($investor->nationality)->name ?? '—' }}</span>
                        <span class="chip"><i class="bi bi-hash"></i> ID: {{ $investor->id }}</span>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('investors.edit', $investor) }}" class="btn btn-primary">
                    <i class="bi bi-pencil-square me-1"></i> تعديل
                </a>
                <a href="{{ route('investors.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-right-circle me-1"></i> العودة للقائمة
                </a>
            </div>
        </div>
    </div>

    {{-- ====== KPIs ====== --}}
    <div class="row g-3 mb-2">
        {{-- نسبة المكتب --}}
        <div class="col-12 col-md-3">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-percent fs-5 text-primary"></i></div>
                    <div class="fw-bold text-muted">نسبة المكتب</div>
                </div>
                <div class="fs-2 fw-bold">{{ number_format($pct, 2) }}%</div>
                <div class="small text-muted">Office Share Percentage</div>
            </div>
        </div>

        {{-- سيولة المستثمر --}}
        <div class="col-12 col-md-3">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-cash-coin fs-5 text-success"></i></div>
                    <div class="fw-bold text-muted">سيولة المستثمر</div>
                </div>
                <div class="fs-2 fw-bold {{ $liquidity >= 0 ? 'text-pos' : 'text-danger' }}">
                    {{ number_format($liquidity, 2) }}
                    <span class="fs-6 text-muted">{{ $currencySymbol }}</span>
                </div>
                <div class="small text-muted">
                    {{ $liquidity >= 0 ? 'صافي الرصيد المتاح' : 'صافي الرصيد المستحق' }}
                </div>
            </div>
        </div>

        {{-- عدد الملفات المرفوعة --}}
        <div class="col-12 col-md-3">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-paperclip fs-5 text-info"></i></div>
                    <div class="fw-bold text-muted">الملفات المرفوعة</div>
                </div>
                <div class="fs-2 fw-bold">{{ $filesCount }}</div>
                <div class="small text-muted">هوية: {{ $hasIdCard ? '✓' : '—' }} — عقد: {{ $hasContract ? '✓' : '—' }}</div>
            </div>
        </div>

        {{-- الإنشاء/التحديث --}}
        <div class="col-12 col-md-3">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-clock-history fs-5 text-primary"></i></div>
                    <div class="fw-bold text-muted">تاريخ الإنشاء / آخر تحديث</div>
                </div>
                <div class="fw-bold">{{ optional($investor->created_at)->format('Y-m-d') ?? '—' }}</div>
                <div class="small text-muted">آخر تحديث: {{ optional($investor->updated_at)->format('Y-m-d H:i') ?? '—' }}</div>
            </div>
        </div>
    </div>

    {{-- ====== تفاصيل المستثمر ====== --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-bold">بيانات أساسية</div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-6">
                    <div class="row">
                        <div class="col-5 label-col">الاسم</div>
                        <div class="col-7 value-col">{{ $investor->name }}</div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-5 label-col">رقم الهوية</div>
                        <div class="col-7 value-col" dir="ltr">
                            @if($investor->national_id)
                                <span>{{ $investor->national_id }}</span>
                                <button class="btn btn-light btn-sm ms-1" onclick="copyText('{{ $investor->national_id }}')" title="نسخ">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-5 label-col">الجنسية</div>
                        <div class="col-7 value-col">{{ optional($investor->nationality)->name ?? '—' }}</div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-5 label-col">الوظيفة</div>
                        <div class="col-7 value-col">{{ optional($investor->title)->name ?? '—' }}</div>
                    </div>
                </div>

                <div class="col-md-6">

                    <div class="row">
                        <div class="col-5 label-col">الهاتف</div>
                        <div class="col-7 value-col" dir="ltr">
                            @if($investor->phone)
                                <a href="tel:{{ $investor->phone }}">{{ $investor->phone }}</a>
                                <button class="btn btn-light btn-sm ms-1" onclick="copyText('{{ $investor->phone }}')" title="نسخ">
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
                            @if($investor->email)
                                <a href="mailto:{{ $investor->email }}">{{ $investor->email }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-5 label-col">العنوان</div>
                        <div class="col-7 value-col">{{ $investor->address ?? '—' }}</div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-5 label-col">نسبة المكتب</div>
                        <div class="col-7 value-col">{{ number_format($pct, 2) }}%</div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ====== الملفات: هوية + عقد ====== --}}
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">صورة الهوية</div>
                <div class="card-body">
                    @if($hasIdCard)
                        <a href="{{ asset('storage/'.$investor->id_card_image) }}" target="_blank" title="عرض بالحجم الكامل">
                            <img class="img-thumb" src="{{ asset('storage/'.$investor->id_card_image) }}" alt="صورة الهوية">
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
                <div class="card-header bg-white fw-bold">صورة العقد</div>
                <div class="card-body">
                    @if($hasContract)
                        <a href="{{ asset('storage/'.$investor->contract_image) }}" target="_blank" title="عرض بالحجم الكامل">
                            <img class="img-thumb" src="{{ asset('storage/'.$investor->contract_image) }}" alt="صورة العقد">
                        </a>
                        <div class="small text-muted mt-2">انقر لفتح الصورة في نافذة جديدة</div>
                    @else
                        <div class="text-muted">لا توجد صورة عقد مرفوعة.</div>
                    @endif
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
