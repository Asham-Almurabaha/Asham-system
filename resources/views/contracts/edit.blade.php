@extends('layouts.master')

@section('title', 'تعديل عقد')

@section('content')
<div class="pagetitle">
    <h1>تعديل عقد</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('contracts.index') }}">العقود</a></li>
            <li class="breadcrumb-item active">تعديل</li>
        </ol>
    </nav>
</div>

<div class="card">
    <div class="card-body p-3">
        <form id="contract-form" action="{{ route('contracts.update', $contract) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- نموذج موحّد (نمرر كائن العقد لاستخدام قيمه الافتراضية/الصور/المستثمرين) --}}
            @include('contracts._form', ['contract' => $contract])

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                <a href="{{ route('contracts.index') }}" class="btn btn-secondary" type="button">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
