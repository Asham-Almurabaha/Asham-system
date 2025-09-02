@extends('layouts.master')

@section('title', __('guarantors.Guarantors List'))

@section('content')

<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">{{ __('guarantors.Guarantors List') }}</h1>
    <nav><ol class="breadcrumb"><li class="breadcrumb-item active">{{ __('guarantors.Guarantors') }}</li></ol></nav>
</div>

@php
    $allTotal    = (int)($guarantorsTotalAll ?? 0);
    $allActive   = (int)($activeGuarantorsTotalAll ?? 0);
    $allInactive = max($allTotal - $allActive, 0);

    $activePct   = $allTotal > 0 ? round(($allActive   / $allTotal) * 100, 1) : 0;
    $inactivePct = $allTotal > 0 ? round(($allInactive / $allTotal) * 100, 1) : 0;

    $newThisMonthAll = (int)($newGuarantorsThisMonthAll ?? 0);
    $newThisWeekAll  = (int)($newGuarantorsThisWeekAll  ?? 0);
@endphp

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">



{{-- ====== الكروت ====== --}}
<div class="row g-4 mb-3" dir="rtl">
    <div class="col-12 col-md-3">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-people fs-4 text-primary"></i></div>
                <div class="flex-grow-1">
                    <div class="subnote">{{ __('guarantors.Total Guarantors — All System') }}</div>
                    <div class="kpi-value fw-bold">{{ number_format($allTotal) }}</div>
                    <div class="subnote">{{ __('guarantors.Not affected by filters') }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-person-check fs-4 text-success"></i></div>
                <div class="flex-grow-1">
                    <div class="subnote">{{ __('guarantors.Active Guarantors') }}</div>
                    <div class="kpi-value fw-bold">{{ number_format($allActive) }}</div>
                    <div class="subnote">{{ __('guarantors.Active Percentage') }}: {{ number_format($activePct,1) }}%</div>
                </div>
            </div>
            <div class="mt-3">
                <div class="progress bar-8"><div class="progress-bar" style="width: {{ $activePct }}%"></div></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-person-x fs-4 text-danger"></i></div>
                <div class="flex-grow-1">
                    <div class="subnote">{{ __('guarantors.Inactive') }}</div>
                    <div class="kpi-value fw-bold">{{ number_format($allInactive) }}</div>
                    <div class="subnote">{{ __('guarantors.Percentage') }}: {{ number_format($inactivePct,1) }}%</div>
                </div>
            </div>
            <div class="mt-3">
                <div class="progress bar-8"><div class="progress-bar bg-danger" style="width: {{ $inactivePct }}%"></div></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-calendar2-plus fs-4 text-primary"></i></div>
                <div class="flex-grow-1">
                    <div class="subnote">{{ __('guarantors.New Guarantors This Month') }}</div>
                    <div class="kpi-value fw-bold">{{ number_format($newThisMonthAll) }}</div>
                    <div class="subnote">{{ __('guarantors.This Week') }}: {{ number_format($newThisWeekAll) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ====== شريط الأدوات ====== --}}
<div class="card shadow-sm mb-3">
  <div class="card-body d-flex flex-wrap gap-2 align-items-center p-2">

    <div class="btn-group" role="group" aria-label="Actions">
      <a href="{{ route('guarantors.create') }}" class="btn btn-success">
        <i class="bi bi-plus-lg"></i> {{ __('guarantors.Add Guarantor') }}
      </a>
      @role('admin')
        <a href="{{ route('guarantors.import.form') }}" class="btn btn-outline-primary">
            <i class="bi bi-upload"></i> {{ __('guarantors.Import Excel') }}
        </a>
    @endrole

      {{-- 🔥 شيلنا زر "تمبليت" زى العملاء --}}
    </div>

    <span class="ms-auto small text-muted">
      {{ __('guarantors.Results') }}: <strong>{{ $guarantors->total() }}</strong>
    </span>

    <button class="btn btn-outline-secondary btn-sm" type="button"
            data-bs-toggle="collapse" data-bs-target="#filterBar"
            aria-expanded="false" aria-controls="filterBar">
      {{ __('guarantors.Filter') }}
    </button>
  </div>

  <div class="collapse @if(request()->hasAny(['guarantor_q','national_id','phone'])) show @endif border-top" id="filterBar">
    <div class="card-body">
      <form id="filterForm" action="{{ route('guarantors.index') }}" method="GET" class="row gy-2 gx-2 align-items-end">
        {{-- ✅ بحث باسم الكفيل فقط --}}
        <div class="col-12 col-md-4">
          <label class="form-label mb-1">{{ __('guarantors.Guarantor (by name)') }}</label>
          <input type="text"
                 name="guarantor_q"
                 value="{{ request('guarantor_q') }}"
                 class="form-control form-control-sm auto-submit-input"
                 placeholder="{{ __('guarantors.Type guarantor name...') }}">
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label mb-1">{{ __('guarantors.National ID') }}</label>
          <input type="text" name="national_id" value="{{ request('national_id') }}"
                 class="form-control form-control-sm auto-submit-input" placeholder="{{ __('guarantors.1234567890') }}">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">{{ __('guarantors.Phone') }}</label>
          <input type="text" name="phone" value="{{ request('phone') }}"
                 class="form-control form-control-sm auto-submit-input" placeholder="{{ __('guarantors.+9665XXXXXXXX') }}">
        </div>

        <div class="col-12 col-md-2">
          <a href="{{ route('guarantors.index') }}" class="btn btn-outline-secondary btn-sm w-100">{{ __('guarantors.Clear') }}</a>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- ====== الجدول ====== --}}
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center mb-0">
                <thead class="table-light position-sticky top-0" style="z-index: 1;">
                    <tr>
                        <th style="width:60px">{{ __('guarantors.#') }}</th>
                        <th>{{ __('guarantors.Name') }}</th>
                        <th>{{ __('guarantors.National ID') }}</th>
                        <th>{{ __('guarantors.Phone') }}</th>
                        <th>{{ __('guarantors.Email') }}</th>
                        <th>{{ __('guarantors.Nationality') }}</th>
                        <th>{{ __('guarantors.Address') }}</th>
                        <th>{{ __('guarantors.Job Title') }}</th>
                        <th style="min-width:110px;">{{ __('guarantors.ID Card Image') }}</th>
                        <th style="width:150px">{{ __('guarantors.Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($guarantors as $g)
                        <tr>
                            <td class="text-muted">
                                {{ $loop->iteration + ($guarantors->currentPage() - 1) * $guarantors->perPage() }}
                            </td>
                            <td class="text-start">{{ $g->name }}</td>
                            <td dir="ltr">{{ $g->national_id ?? '—' }}</td>
                            <td dir="ltr">{{ $g->phone ?? '—' }}</td>
                            <td class="text-start">{{ $g->email ?? '—' }}</td>
                            <td>{{ optional($g->nationality)->name ?? '—' }}</td>
                            <td class="text-start">{{ $g->address ?? '—' }}</td>
                            <td>{{ optional($g->title)->name ?? '—' }}</td>
                            <td>
                                @if($g->id_card_image)
                                    <a href="{{ asset('storage/' . $g->id_card_image) }}" target="_blank" data-bs-toggle="tooltip" title="{{ __('guarantors.View full size image') }}">
                                        <img src="{{ asset('storage/' . $g->id_card_image) }}"
                                             alt="{{ __('guarantors.ID Card Image') }}" width="70" height="48"
                                             style="object-fit: cover; border-radius: .25rem;">
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ route('guarantors.show', $g) }}" class="btn btn-outline-secondary btn-sm">{{ __('guarantors.View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-5">
                                <div class="text-muted">
                                    {{ __('guarantors.No matching results for your search.') }}
                                    <a href="{{ route('guarantors.index') }}" class="ms-1">{{ __('guarantors.Show All') }}</a>
                                </div>
                                <div class="mt-3">
                                    <a href="{{ route('guarantors.create') }}" class="btn btn-sm btn-success">
                                        + {{ __('guarantors.Add First Guarantor') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($guarantors->hasPages())
    <div class="card-footer bg-white">
        {{ $guarantors->withQueryString()->links('pagination::bootstrap-5') }}
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

    // auto submit للمدخلات النصية مع تأخير بسيط
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

