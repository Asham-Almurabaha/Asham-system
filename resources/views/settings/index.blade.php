@extends('layouts.master')
@section('title', __('Settings'))

@section('content')
<div class="container py-3">

  {{-- Breadcrumbs --}}
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('لوحة التحكم') }}</a></li>
      <li class="breadcrumb-item active" aria-current="page">{{ __('الإعدادات العامة') }}</li>
    </ol>
  </nav>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h4 class="mb-0">{{ __('الإعدادات العامة') }}</h4>
      <small class="text-muted">{{ __('التحكم في الاسم والشعار و أيقونة الموقع') }}</small>
    </div>

    <div class="d-flex gap-2">
      @if($setting)
        <a href="{{ route('settings.show', $setting) }}" class="btn btn-outline-secondary">
          <i class="bi bi-eye me-1"></i>{{ __('عرض') }}
        </a>
        <a href="{{ route('settings.edit', $setting) }}" class="btn btn-primary">
          <i class="bi bi-pencil-square me-1"></i>{{ __('تعديل') }}
        </a>
        <form action="{{ route('settings.destroy', $setting) }}" method="POST"
              onsubmit="return confirm('{{ __('سيتم حذف الإعداد والصور. هل أنت متأكد؟') }}')">
          @csrf @method('DELETE')
          <button class="btn btn-danger">
            <i class="bi bi-trash me-1"></i>{{ __('حذف') }}
          </button>
        </form>
      @else
        <a href="{{ route('settings.create') }}" class="btn btn-success">
          <i class="bi bi-plus-circle me-1"></i>{{ __('إنشاء إعداد') }}
        </a>
      @endif
    </div>
  </div>

  @if(!$setting)
    <div class="card shadow-sm mt">
      <div class="card-body text-center py-5">
        <div class="mb-3"><i class="bi bi-gear-wide-connected fs-1"></i></div>
        <h5 class="mb-2">{{ __('لا يوجد إعداد محفوظ بعد') }}</h5>
        <p class="text-muted mb-4">{{ __('أنشئ الإعداد لإظهار الاسم والشعار وأيقونة الموقع عبر النظام.') }}</p>
        <a href="{{ route('settings.create') }}" class="btn btn-success">{{ __('إنشاء الآن') }}</a>
      </div>
    </div>
  @else
    {{-- Summary + Media --}}
    <div class="row g-3">
      <div class="col-lg-8">
        <div class="card shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3 mt-2">
              <h5 class="mb-0 mt">{{ __('البيانات الأساسية') }}</h5>
              <span class="badge bg-success-subtle text-success border">{{ __('نشط') }}</span>
            </div>

            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <tbody>
                  <tr>
                    <th style="width:220px">{{ __('الاسم (EN)') }}</th>
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

          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card shadow-sm h-100">
          <div class="card-body mt-2">
            <h6 class="mb-3">{{ __('الوسائط') }}</h6>

            <div class="d-flex gap-4 flex-wrap">
              {{-- Logo --}}
              <div class="text-center">
                <div class="text-muted small mb-2">{{ __('الشعار') }}</div>
                @if(!empty($setting->logo_url))
                  <div class="ratio ratio-1x1 border rounded bg-light d-flex align-items-center justify-content-center" style="width:96px;">
                    <img src="{{ $setting->logo_url }}" alt="Logo" class="img-fluid p-1">
                  </div>
                @else
                  <div class="text-muted fst-italic">{{ __('غير مرفوع') }}</div>
                @endif
              </div>

              {{-- Favicon --}}
              <div class="text-center">
                <div class="text-muted small mb-2">{{ __('Favicon') }}</div>
                @if(!empty($setting->favicon_url))
                  <div class="ratio ratio-1x1 border rounded bg-light d-flex align-items-center justify-content-center" style="width:64px;">
                    <img src="{{ $setting->favicon_url }}" alt="Favicon" class="img-fluid p-1">
                  </div>
                @else
                  <div class="text-muted fst-italic">{{ __('غير مرفوع') }}</div>
                @endif
              </div>
            </div>

            <div class="mt-3 d-flex gap-2">
              <a href="{{ route('settings.show', $setting) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-eye me-1"></i>{{ __('عرض التفاصيل') }}
              </a>
              <a href="{{ route('settings.edit', $setting) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil me-1"></i>{{ __('تعديل') }}
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Branding Preview --}}
    <div class="card shadow-sm mt-3">
      <div class="card-body mt-2">
        <h6 class="mb-3">{{ __('معاينة العلامة') }}</h6>
        <div class="border rounded p-3 d-flex align-items-center gap-3">
          @if(!empty($setting->logo_url))
            <img src="{{ $setting->logo_url }}" alt="Logo" style="height:40px" class="rounded border bg-white p-1">
          @else
            <div class="rounded border bg-light d-flex align-items-center justify-content-center" style="height:40px;width:40px;">
              <i class="bi bi-image text-muted"></i>
            </div>
          @endif
          <div class="fs-5 fw-semibold">{{ app()->getLocale()==='ar' ? ($setting->name_ar ?? $setting->name) : ($setting->name ?? $setting->name_ar) }}</div>
        </div>
      </div>
    </div>
  @endif
</div>
@endsection
