{{-- resources/views/contracts/export.blade.php --}}
@extends('layouts.master')

@section('title', 'تصدير أمثلة العقود')

@section('content')
<div class="container-xxl py-4" dir="rtl">
  <div class="rounded-3 p-4 mb-4 position-relative overflow-hidden bg-light border">
    <div class="d-flex align-items-center gap-3">
      <div class="rounded-4 bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width:54px;height:54px;">
        <i class="bi bi-cloud-arrow-down fs-3"></i>
      </div>
      <div>
        <h1 class="h4 mb-1">تصدير أمثلة العقود</h1>
        <p class="text-muted mb-0">اختر نوع البيانات لتصدير ملف Excel.</p>
      </div>
    </div>
  </div>

  <div class="d-flex flex-column flex-md-row gap-3">
    <x-button href="{{ route('contracts.export.basic') }}" variant="primary">
      <i class="bi bi-filetype-xlsx me-1"></i> تصدير البيانات الأساسية (المثال)
    </x-button>
    <x-button href="{{ route('contracts.export.investors') }}" variant="info">
      <i class="bi bi-filetype-xlsx me-1"></i> تصدير المستثمرين (النسب غير المكتملة)
    </x-button>
    <x-button href="{{ route('contracts.export.payments') }}" variant="success">
      <i class="bi bi-filetype-xlsx me-1"></i> تصدير الأقساط (المبلغ المتبقي)
    </x-button>
  </div>
</div>
@endsection

