@extends('layouts.master')

@section('title', 'عرض بيانات الكفيل')

@section('content')

<div class="pagetitle">
    <h1>عرض بيانات الكفيل</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">الكفلاء</li>
            <li class="breadcrumb-item active">عرض</li>
        </ol>
    </nav>
</div>

<section class="section profile">
    <div class="card">
        <div class="card-body pt-3">

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">الاسم</div>
                <div class="col-lg-9 col-md-8">{{ $guarantor->name }}</div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">رقم الهوية الوطنية</div>
                <div class="col-lg-9 col-md-8">{{ $guarantor->national_id ?? '-' }}</div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">الهاتف</div>
                <div class="col-lg-9 col-md-8">{{ $guarantor->phone ?? '-' }}</div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">البريد الإلكتروني</div>
                <div class="col-lg-9 col-md-8">{{ $guarantor->email ?? '-' }}</div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">الجنسية</div>
                <div class="col-lg-9 col-md-8">{{ $guarantor->nationality ? $guarantor->nationality->name : '-' }}</div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">الوظيفة</div>
                <div class="col-lg-9 col-md-8">{{ $guarantor->title ? $guarantor->title->name : '-' }}</div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">العنوان</div>
                <div class="col-lg-9 col-md-8">{{ $guarantor->address ?? '-' }}</div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">صورة الهوية</div>
                <div class="col-lg-9 col-md-8">
                    @if($guarantor->id_card_image)
                        <a href="{{ asset('storage/' . $guarantor->id_card_image) }}" target="_blank">
                            <img src="{{ asset('storage/' . $guarantor->id_card_image) }}" alt="صورة الهوية" style="max-width: 150px; object-fit: cover;">
                        </a>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">ملاحظات</div>
                <div class="col-lg-9 col-md-8">{{ $guarantor->notes ?? '-' }}</div>
            </div>

            <a href="{{ route('guarantors.index') }}" class="btn btn-secondary mt-3">العودة للقائمة</a>

        </div>
    </div>
</section>

@endsection
