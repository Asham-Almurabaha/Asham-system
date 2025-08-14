@extends('layouts.master')

@section('title', 'عرض بيانات العميل')

@section('content')

<div class="pagetitle">
    <h1>عرض بيانات العميل</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">العملاء</li>
            <li class="breadcrumb-item active">عرض</li>
        </ol>
    </nav>
</div>

<section class="section profile">
    <div class="card">
        <div class="card-body pt-3">

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">الاسم</div>
                <div class="col-lg-9 col-md-8">{{ $customer->name }}</div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">رقم الهوية الوطنية</div>
                <div class="col-lg-9 col-md-8">{{ $customer->national_id ?? '-' }}</div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">الهاتف</div>
                <div class="col-lg-9 col-md-8">{{ $customer->phone ?? '-' }}</div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">البريد الإلكتروني</div>
                <div class="col-lg-9 col-md-8">{{ $customer->email ?? '-' }}</div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">الجنسية</div>
                <div class="col-lg-9 col-md-8">{{ $customer->nationality ? $customer->nationality->name : '-' }}</div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">العنوان</div>
                <div class="col-lg-9 col-md-8">{{ $customer->address ?? '-' }}</div>
            </div>


            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">الوظيفة</div>
                <div class="col-lg-9 col-md-8">{{ $customer->title ? $customer->title->name : '-' }}</div>
            </div>


            

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">صورة الهوية</div>
                <div class="col-lg-9 col-md-8">
                    @if($customer->id_card_image)
                        <a href="{{ asset('storage/' . $customer->id_card_image) }}" target="_blank">
                            <img src="{{ asset('storage/' . $customer->id_card_image) }}" alt="صورة الهوية" style="max-width: 150px; object-fit: cover;">
                        </a>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">ملاحظات</div>
                <div class="col-lg-9 col-md-8">{{ $customer->notes ?? '-' }}</div>
            </div>

            <a href="{{ route('customers.index') }}" class="btn btn-secondary mt-3">العودة للقائمة</a>

        </div>
    </div>
</section>

@section('js')
<script>
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
</script>
@endsection

@endsection
