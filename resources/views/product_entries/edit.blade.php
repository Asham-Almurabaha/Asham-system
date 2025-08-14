@extends('layouts.master')

@section('title', 'تعديل الإدخال')

@section('content')

<div class="pagetitle">
    <h1>تعديل الإدخال</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Setting</li>
            <li class="breadcrumb-item">Product Entries</li>
            <li class="breadcrumb-item active">Edit</li>
        </ol>
    </nav>
</div>

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
            <form action="{{ route('product_entries.update', $productEntry->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="product_id" class="form-label">المنتج</label>
                    <select name="product_id" id="product_id" class="form-select" required>
                        @foreach ($products as $product)
                        <option value="{{ $product->id }}" {{ old('product_id', $productEntry->product_id) == $product->id ? 'selected' : '' }}>
                            {{ $product->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="quantity" class="form-label">الكمية</label>
                    <input type="number" min="1" name="quantity" id="quantity" class="form-control" value="{{ old('quantity', $productEntry->quantity) }}" required>
                </div>

                <div class="mb-3">
                    <label for="purchase_price" class="form-label">سعر الشراء</label>
                    <input type="number" min="0" step="0.01" name="purchase_price" id="purchase_price" class="form-control" value="{{ old('purchase_price', $productEntry->purchase_price) }}" required>
                </div>

                <div class="mb-3">
                    <label for="entry_date" class="form-label">تاريخ الإدخال</label>
                    <input type="date" name="entry_date" id="entry_date" class="form-control" value="{{ old('entry_date', $productEntry->entry_date->format('Y-m-d')) }}" required>
                </div>

                <button type="submit" class="btn btn-primary">تحديث</button>
                <a href="{{ route('product_entries.index') }}" class="btn btn-secondary">إلغاء</a>
            </form>
        </div>
    </div>
</div>

@endsection
