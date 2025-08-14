@extends('layouts.master')

@section('title', 'إضافة حساب جديد')

@section('content')

<div class="pagetitle">
    <h1>إضافة حساب جديد</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">الإعدادات</li>
            <li class="breadcrumb-item">الحسابات</li>
            <li class="breadcrumb-item active">إضافة</li>
        </ol>
    </nav>
</div><!-- End Page Title -->

@if ($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="col-lg-6">
    <div class="card">
        <div class="card-body p-20">
            <form action="{{ route('bank_cash_accounts.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">اسم الحساب</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="type" class="form-label">النوع</label>
                    <select name="type" id="type" class="form-select" required>
                        <option value="">اختر النوع</option>
                        <option value="bank" {{ old('type') == 'bank' ? 'selected' : '' }}>بنكي</option>
                        <option value="cash" {{ old('type') == 'cash' ? 'selected' : '' }}>خزينة نقدية</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="account_number" class="form-label">رقم الحساب</label>
                    <input type="text" name="account_number" id="account_number" class="form-control" value="{{ old('account_number') }}">
                </div>

                <div class="mb-3">
                    <label for="branch" class="form-label">الفرع</label>
                    <input type="text" name="branch" id="branch" class="form-control" value="{{ old('branch') }}">
                </div>

                <div class="mb-3">
                    <label for="balance" class="form-label">الرصيد الابتدائي</label>
                    <input type="number" step="0.01" name="balance" id="balance" class="form-control" value="{{ old('balance') ?? 0 }}">
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">ملاحظات</label>
                    <textarea name="notes" id="notes" class="form-control">{{ old('notes') }}</textarea>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="1" id="active" name="active" {{ old('active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="active">
                        نشط
                    </label>
                </div>

                <button type="submit" class="btn btn-success">حفظ</button>
                <a href="{{ route('bank_cash_accounts.index') }}" class="btn btn-secondary">إلغاء</a>
            </form>
        </div>
    </div>
</div>

@endsection
