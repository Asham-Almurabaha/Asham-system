@extends('layouts.master')

@section('title', 'تعديل نوع العملية')

@section('content')

<div class="pagetitle">
    <h1>تعديل نوع العملية</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Setting</li>
            <li class="breadcrumb-item">Transaction Types</li>
            <li class="breadcrumb-item active">Edit</li>
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
            <form action="{{ route('transaction_types.update', $transactionType->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label">اسم نوع العملية</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $transactionType->name) }}" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">الوصف (اختياري)</label>
                    <textarea name="description" id="description" class="form-control">{{ old('description', $transactionType->description) }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">تحديث</button>
                <a href="{{ route('transaction_types.index') }}" class="btn btn-secondary">إلغاء</a>
            </form>
        </div>
    </div>
</div>

@endsection
