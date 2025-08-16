@extends('layouts.master')

@section('title', 'إضافة عملية مستثمر')

@section('content')
<div class="pagetitle">
    <h1>إضافة عملية مستثمر</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('investor-transactions.index') }}">عمليات المستثمرين</a></li>
            <li class="breadcrumb-item active">إضافة عملية</li>
        </ol>
    </nav>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('investor-transactions.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">المستثمر</label>
                    <select name="investor_id" class="form-select" required>
                        <option value="" disabled selected>اختر المستثمر</option>
                        @foreach ($investors as $investor)
                        <option value="{{ $investor->id }}" {{ old('investor_id') == $investor->id ? 'selected' : '' }}>{{ $investor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">الحالة</label>
                    <select name="status_id" class="form-select" required>
                        <option value="" disabled selected>اختر الحالة</option>
                        @foreach ($statuses as $status)
                        <option value="{{ $status->id }}" {{ old('status_id') == $status->id ? 'selected' : '' }}>{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">المبلغ</label>
                    <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">تاريخ العملية</label>
                    <input type="date" name="transaction_date" class="form-control" value="{{ old('transaction_date') }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label">ملاحظات</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">حفظ</button>
                <a href="{{ route('investor-transactions.index') }}" class="btn btn-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection