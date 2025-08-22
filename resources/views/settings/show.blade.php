@extends('layouts.master')
@section('title', __('View Settings'))

@section('content')
<div class="container py-3">

  {{-- Breadcrumbs --}}
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('لوحة التحكم') }}</a></li>
      <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">{{ __('الإعدادات العامة') }}</a></li>
      <li class="breadcrumb-item active" aria-current="page">{{ __('عرض') }}</li>
    </ol>
  </nav>

  {{-- Header / Toolbar --}}
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center gap-3">
      @if(!empty($setting->logo_url))
        <img src="{{ $setting->logo_url }}" alt="Logo" class="rounded border bg-white p-1" style="height:48px">
      @else
        <div class="rounded border bg-light d-flex align-items-center justify-content-center" style="height:48px;width:48px;">
          <i class="bi bi-image text-muted fs-5"></i>
        </div>
      @endif
      <div>
        <h4 class="mb-0">{{ app()->getLocale()==='ar' ? ($setting->name_ar ?? $setting->name) : ($setting->name ?? $setting->name_ar) }}</h4>
        <small class="text-muted">{{ __('تفاصيل الإعداد العام للنظام') }}</small>
      </div>
    </div>

    <div class="d-flex gap-2">
      <a href="{{ route('settings.edit', $setting) }}" class="btn btn-primary">
        <i class="bi bi-pencil-square me-1"></i>{{ __('تعديل') }}
      </a>
      <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-right-circle me-1"></i>{{ __('رجوع') }}
      </a>
    </div>
  </div>

  <div class="row g-3">
    {{-- Details --}}
    <div class="col-lg-7">
      <div class="card shadow-sm h-100">
        <div class="card-body mt-2">
          <h6 class="mb-3">{{ __('البيانات الأساسية') }}</h6>
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <tbody>
                <tr>
                  <th style="width:220px">{{ __('اسم المالك') }}</th>
                  <td class="fw-medium">{{ $setting->owner_name }}</td>
                </tr>
                <tr>
                  <th>{{ __('الاسم (EN)') }}</th>
                  <td class="fw-medium">{{ $setting->name }}</td>
                </tr>
                <tr>
                  <th>{{ __('الاسم (AR)') }}</th>
                  <td class="fw-medium">{{ $setting->name_ar }}</td>
                </tr>
                <tr>
                  <th>{{ __('تاريخ الإنشاء') }}</th>
                  <td>
                    {{ $setting->created_at?->format('Y-m-d H:i') }}
                    <span class="text-muted">— {{ $setting->created_at?->diffForHumans() }}</span>
                  </td>
                </tr>
                <tr>
                  <th>{{ __('آخر تحديث') }}</th>
                  <td>
                    {{ $setting->updated_at?->format('Y-m-d H:i') }}
                    <span class="text-muted">— {{ $setting->updated_at?->diffForHumans() }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          {{-- Danger Zone --}}
          <div class="mt-4">
            <h6 class="text-danger mb-2"><i class="bi bi-exclamation-triangle me-1"></i>{{ __('منطقة خطرة') }}</h6>
            <div class="border border-danger-subtle rounded p-3">
              <p class="mb-3 text-danger small">{{ __('سيتم حذف السجل والصور المرتبطة به نهائيًا.') }}</p>
              <form action="{{ route('settings.destroy', $setting) }}" method="POST"
                    onsubmit="return confirm('{{ __('هل أنت متأكد من حذف هذا الإعداد؟ لا يمكن التراجع عن العملية.') }}')">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger">
                  <i class="bi bi-trash me-1"></i>{{ __('حذف الإعداد') }}
                </button>
              </form>
            </div>
          </div>

        </div>
      </div>
    </div>

    {{-- Media Preview --}}
    <div class="col-lg-5">
      <div class="card shadow-sm h-100">
        <div class="card-body mt-2">
          <h6 class="mb-3">{{ __('المعاينات') }}</h6>

          {{-- Logo Preview --}}
          <div class="mb-4">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="text-muted">{{ __('الشعار') }}</span>
              @if(!empty($setting->logo_url))
                <a href="{{ $setting->logo_url }}" target="_blank" class="small">{{ __('فتح الأصل') }}</a>
              @endif
            </div>
            @if(!empty($setting->logo_url))
              <div class="border rounded p-3 bg-light d-flex align-items-center" style="min-height:90px;">
                <img src="{{ $setting->logo_url }}" alt="Logo" class="img-fluid" style="max-height:64px">
              </div>
            @else
              <div class="border rounded p-3 bg-light d-flex align-items-center justify-content-center" style="min-height:90px;">
                <span class="text-muted fst-italic">{{ __('لا يوجد شعار مرفوع') }}</span>
              </div>
            @endif
          </div>

          {{-- Favicon Preview --}}
          <div>
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="text-muted">{{ __('Favicon') }}</span>
              @if(!empty($setting->favicon_url))
                <a href="{{ $setting->favicon_url }}" target="_blank" class="small">{{ __('فتح الأصل') }}</a>
              @endif
            </div>
            @if(!empty($setting->favicon_url))
              <div class="d-flex align-items-center gap-3">
                <div class="border rounded bg-light d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                  <img src="{{ $setting->favicon_url }}" alt="Favicon" class="img-fluid p-1" style="max-height:32px">
                </div>
                <div class="text-muted small">{{ __('معاينة افتراضية لحجم الأيقونة') }}</div>
              </div>
            @else
              <div class="border rounded p-3 bg-light d-flex align-items-center justify-content-center" style="min-height:64px;">
                <span class="text-muted fst-italic">{{ __('لا توجد أيقونة مرفوعة') }}</span>
              </div>
            @endif
          </div>

          {{-- Branding Inline Preview --}}
          <hr class="my-4">
          <div>
            <div class="text-muted small mb-2">{{ __('معاينة مدمجة') }}</div>
            <div class="border rounded p-3 d-flex align-items-center gap-3">
              @if(!empty($setting->logo_url))
                <img src="{{ $setting->logo_url }}" alt="Logo" style="height:32px" class="rounded border bg-white p-1">
              @else
                <div class="rounded border bg-light d-flex align-items-center justify-content-center" style="height:32px;width:32px;">
                  <i class="bi bi-image text-muted"></i>
                </div>
              @endif
              <div class="fw-semibold">
                {{ app()->getLocale()==='ar' ? ($setting->name_ar ?? $setting->name) : ($setting->name ?? $setting->name_ar) }}
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

  </div>
</div>
@endsection
