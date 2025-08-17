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

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <form action="{{ route('investor-transactions.store') }}" method="POST" class="mt-3">
            @csrf
            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">المستثمر</label>
                    <select name="investor_id" class="form-select" required>
                        <option value="" disabled {{ old('investor_id') ? '' : 'selected' }}>اختر المستثمر</option>
                        @foreach ($investors as $investor)
                            <option value="{{ $investor->id }}" {{ old('investor_id') == $investor->id ? 'selected' : '' }}>
                                {{ $investor->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('investor_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">الحالة</label>
                    <select name="status_id" class="form-select" required {{ $statuses->count() ? '' : 'disabled' }}>
                        <option value="" disabled {{ old('status_id') ? '' : 'selected' }}>
                            {{ $statuses->count() ? 'اختر الحالة' : 'لا توجد حالات مرتبطة بفئة المستثمرين' }}
                        </option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->id }}" {{ old('status_id') == $status->id ? 'selected' : '' }}>
                                {{ $status->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('status_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">المبلغ</label>
                    <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required>
                    @error('amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">تاريخ العملية</label>
                    <input
                        type="text"
                        name="transaction_date"
                        class="form-control js-date"
                        value="{{ old('transaction_date', now()->format('Y-m-d')) }}"
                        placeholder="YYYY-MM-DD"
                        autocomplete="off"
                        required
                    >
                    @error('transaction_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label">ملاحظات</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                    @error('notes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary" {{ $statuses->count() ? '' : 'disabled' }}>حفظ</button>
                <a href="{{ route('investor-transactions.index') }}" class="btn btn-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
