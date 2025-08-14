@extends('layouts.master')

@section('title', 'عرض بيانات المستثمر')

@section('content')

<div class="pagetitle">
    <h1>عرض بيانات المستثمر</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">المستثمرين</li>
            <li class="breadcrumb-item active">عرض</li>
        </ol>
    </nav>
</div>

<section class="section profile">
    <div class="card">
        <div class="card-body pt-3">

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">الاسم</div>
                <div class="col-lg-9 col-md-8">{{ $investor->name }}</div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">رقم الهوية الوطنية</div>
                <div class="col-lg-9 col-md-8">{{ $investor->national_id ?? '-' }}</div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">الهاتف</div>
                <div class="col-lg-9 col-md-8">{{ $investor->phone ?? '-' }}</div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">البريد الإلكتروني</div>
                <div class="col-lg-9 col-md-8">{{ $investor->email ?? '-' }}</div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">الجنسية</div>
                <div class="col-lg-9 col-md-8">{{ $investor->nationality ? $investor->nationality->name : '-' }}</div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">العنوان</div>
                <div class="col-lg-9 col-md-8">{{ $investor->address ?? '-' }}</div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">الوظيفة</div>
                <div class="col-lg-9 col-md-8">{{ $investor->title ? $investor->title->name : '-' }}</div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">صورة الهوية</div>
                <div class="col-lg-9 col-md-8">
                    @if($investor->id_card_image)
                        <a href="{{ asset('storage/' . $investor->id_card_image) }}" target="_blank">
                            <img src="{{ asset('storage/' . $investor->id_card_image) }}" alt="صورة الهوية" style="max-width: 150px; object-fit: cover;">
                        </a>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">صورة العقد</div>
                <div class="col-lg-9 col-md-8">
                    @if($investor->contract_image)
                        <a href="{{ asset('storage/' . $investor->contract_image) }}" target="_blank">
                            <img src="{{ asset('storage/' . $investor->contract_image) }}" alt="صورة العقد" style="max-width: 150px; object-fit: cover;">
                        </a>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 label">نسبة المكتب (%)</div>
                <div class="col-lg-9 col-md-8">{{ $investor->office_share_percentage ?? '0' }}%</div>
            </div>

            <a href="{{ route('investors.index') }}" class="btn btn-secondary mt-3">العودة للقائمة</a>

        </div>
    </div>
</section>

@endsection
