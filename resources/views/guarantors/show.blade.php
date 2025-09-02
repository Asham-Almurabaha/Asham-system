@extends('layouts.master')

@section('title', __('guarantors.View Guarantor'))

@section('content')
<div class="container py-3" dir="rtl">

    {{-- Bootstrap Icons (لو مش مضافة في الـ layout) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    @php
        // ====== إحصائيات سريعة ======
        $contractsCount = method_exists($guarantor, 'contracts')
            ? (int) $guarantor->contracts()->count()
            : 0;

        // تحديد "العقد النشط": ليس (منتهي/سداد مبكر) مع دعم أكثر من شكل للأعمدة
        $endedNames = ['منتهي','منتهى','سداد مبكر','سداد مُبكر','سداد مبكّر','Completed','Early Settlement'];

        $activeContractsCount = 0;
        if (method_exists($guarantor, 'contracts')) {
            $activeContractsCount = $guarantor->contracts()->where(function ($q) use ($endedNames) {
                // status النصّي
                if (\Illuminate\Support\Facades\Schema::hasColumn('contracts','status')) {
                    $q->whereNotIn('status', $endedNames);

                // status_id أو contract_status_id
                } elseif (\Illuminate\Support\Facades\Schema::hasColumn('contracts','status_id')
                       || \Illuminate\Support\Facades\Schema::hasColumn('contracts','contract_status_id')) {

                    $statusCol = \Illuminate\Support\Facades\Schema::hasColumn('contracts','status_id')
                        ? 'status_id' : 'contract_status_id';

                    if (class_exists(\App\Models\ContractStatus::class)) {
                        $endedIds = \App\Models\ContractStatus::whereIn('name', $endedNames)->pluck('id');
                        if ($endedIds->isNotEmpty()) {
                            $q->whereNotIn($statusCol, $endedIds);
                        } else {
                            $q->whereRaw('1=1'); // ما عندنا IDs واضحة، اعتبر أي عقد نشط
                        }
                    } else {
                        $q->whereRaw('1=1'); // ما في موديل للحالات
                    }

                // is_closed / closed_at كبدائل منطقية
                } elseif (\Illuminate\Support\Facades\Schema::hasColumn('contracts','is_closed')) {
                    $q->where('is_closed', 0);
                } elseif (\Illuminate\Support\Facades\Schema::hasColumn('contracts','closed_at')) {
                    $q->whereNull('closed_at');
                } else {
                    $q->whereRaw('1=1'); // fallback آمن
                }
            })->count();
        }

        $activePct = $contractsCount > 0
            ? round(($activeContractsCount / $contractsCount) * 100, 1)
            : 0;
    @endphp

    

    {{-- ====== HERO ====== --}}
    <div class="profile-hero mb-3">
        <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar">
                    {{ mb_strtoupper(mb_substr($guarantor->name ?? '؟', 0, 1)) }}
                </div>
                <div>
                    <h3 class="mb-0">{{ $guarantor->name }}</h3>
                    <div class="small text-muted-2 mt-1">
                        <span class="chip me-1"><i class="bi bi-badge-ad"></i> {{ optional($guarantor->title)->name ?? '—' }}</span>
                        <span class="chip me-1"><i class="bi bi-flag"></i> {{ optional($guarantor->nationality)->name ?? '—' }}</span>
                        <span class="chip"><i class="bi bi-hash"></i> ID: {{ $guarantor->id }}</span>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('guarantors.edit', $guarantor) }}" class="btn btn-primary">
                    <i class="bi bi-pencil-square me-1"></i> {{ __('guarantors.Edit') }}
                </a>
                <a href="{{ route('guarantors.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-right-circle me-1"></i> {{ __('guarantors.Back to List') }}
                </a>
            </div>
        </div>
    </div>

    {{-- ====== KPIs ====== --}}
    <div class="row g-3 mb-2">
        <div class="col-12 col-md-4">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-files fs-5 text-primary"></i></div>
                    <div class="fw-bold text-muted">{{ __('guarantors.Total Guaranteed Contracts') }}</div>
                </div>
                <div class="fs-2 fw-bold">{{ number_format($contractsCount) }}</div>
                <div class="small text-muted">{{ __('guarantors.All contracts linked to the guarantor') }}</div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-person-check fs-5 text-success"></i></div>
                    <div class="fw-bold text-muted">{{ __('guarantors.Active Contracts') }}</div>
                </div>
                <div class="fs-2 fw-bold text-pos">{{ number_format($activeContractsCount) }}</div>
                <div class="small text-muted">{{ __('guarantors.Ratio:') }} {{ $contractsCount>0 ? number_format($activePct,1) : 0 }}%</div>
                <div class="progress mt-2" style="height:8px;">
                    <div class="progress-bar" role="progressbar" style="width: {{ $activePct }}%"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="kpi-icon"><i class="bi bi-clock-history fs-5 text-primary"></i></div>
                    <div class="fw-bold text-muted">{{ __('guarantors.Creation Date / Last Update') }}</div>
                </div>
                <div class="fw-bold">{{ optional($guarantor->created_at)->format('Y-m-d') ?? '—' }}</div>
                <div class="small text-muted">{{ __('guarantors.Last Update:') }} {{ optional($guarantor->updated_at)->format('Y-m-d H:i') ?? '—' }}</div>
            </div>
        </div>
    </div>

    {{-- ====== تفاصيل الكفيل ====== --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-bold">{{ __('guarantors.Basic Information') }}</div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-6">
                    <div class="row">
                        <div class="col-5 label-col">{{ __('guarantors.Name') }}</div>
                        <div class="col-7 value-col">{{ $guarantor->name }}</div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-5 label-col">{{ __('guarantors.National ID') }}</div>
                        <div class="col-7 value-col">
                            @if($guarantor->national_id)
                                <span>{{ $guarantor->national_id }}</span>
                                <button class="btn btn-light btn-sm ms-1" onclick="copyText('{{ $guarantor->national_id }}')" title="{{ __('guarantors.Copy') }}">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-5 label-col">{{ __('guarantors.Nationality') }}</div>
                        <div class="col-7 value-col">{{ optional($guarantor->nationality)->name ?? '—' }}</div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-5 label-col">{{ __('guarantors.Job Title') }}</div>
                        <div class="col-7 value-col">{{ optional($guarantor->title)->name ?? '—' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="row">
                        <div class="col-5 label-col">{{ __('guarantors.Phone') }}</div>
                        <div class="col-7 value-col">
                            @if($guarantor->phone)
                                <a href="tel:{{ $guarantor->phone }}">{{ $guarantor->phone }}</a>
                                <button class="btn btn-light btn-sm ms-1" onclick="copyText('{{ $guarantor->phone }}')" title="{{ __('guarantors.Copy') }}">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-5 label-col">{{ __('guarantors.Email') }}</div>
                        <div class="col-7 value-col">
                            @if($guarantor->email)
                                <a href="mailto:{{ $guarantor->email }}">{{ $guarantor->email }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-5 label-col">{{ __('guarantors.Address') }}</div>
                        <div class="col-7 value-col">{{ $guarantor->address ?? '—' }}</div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ====== صورة الهوية & الملاحظات ====== --}}
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">{{ __('guarantors.ID Card Image') }}</div>
                <div class="card-body">
                    @if($guarantor->id_card_image)
                        <a href="{{ asset('storage/'.$guarantor->id_card_image) }}" target="_blank" title="{{ __('guarantors.View full size image') }}">
                            <img class="img-thumb" src="{{ asset('storage/'.$guarantor->id_card_image) }}" alt="{{ __('guarantors.ID Card Image') }}">
                        </a>
                        <div class="small text-muted mt-2">{{ __('guarantors.Click to open the image in a new window') }}</div>
                    @else
                        <div class="text-muted">{{ __('guarantors.No ID card image uploaded.') }}</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">{{ __('guarantors.Notes') }}</div>
                <div class="card-body">
                    <div class="text-wrap" style="white-space: pre-line;">
                        {{ $guarantor->notes ?? '—' }}
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
        // Toast خفيف
        const el = document.createElement('div');
        el.textContent = '{{ __('guarantors.Copied') }}';
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

