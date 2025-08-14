@extends('layouts.master')

@section('title', 'إضافة عقد جديد')

@section('content')
<div class="pagetitle">
    <h1>إضافة عقد جديد</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('contracts.index') }}">العقود</a></li>
            <li class="breadcrumb-item active">إضافة</li>
        </ol>
    </nav>
</div>

<div class="card">
    <div class="card-body p-3">
        <form id="contract-form" action="{{ route('contracts.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- نموذج موحّد --}}
            @include('contracts._form')

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">حفظ</button>
                <a href="{{ route('contracts.index') }}" class="btn btn-secondary" type="button">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
