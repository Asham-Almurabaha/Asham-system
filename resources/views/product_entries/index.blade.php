@extends('layouts.master')

@section('title', 'قائمة إدخالات المنتجات')

@section('content')

<div class="pagetitle">
    <h1>قائمة إدخالات المنتجات</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Setting</li>
            <li class="breadcrumb-item active">Product Entries</li>
        </ol>
    </nav>
</div>

<div class="card d-inline-block mb-3">
    <div class="card-body p-20">
        <a href="{{ route('product_entries.create') }}" class="btn btn-success">إضافة إدخال جديد</a>
    </div>
</div>

<div class="col-lg-12">
    <div class="card">
        <div class="card-body p-20">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col" class="col-1">#</th>
                        <th>المنتج</th>
                        <th>الكمية</th>
                        <th>سعر الشراء</th>
                        <th>تاريخ الإدخال</th>
                        <th scope="col" class="col-2">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $entry->product->name }}</td>
                        <td>{{ $entry->quantity }}</td>
                        <td>{{ number_format($entry->purchase_price, 2) }}</td>
                        <td>{{ \Carbon\Carbon::parse($entry->entry_date)->format('Y-m-d') }}</td>
                        <td>
                            <a href="{{ route('product_entries.edit', $entry->id) }}" class="btn btn-primary btn-sm me-1">تعديل</a>
                            <form action="{{ route('product_entries.destroy', $entry->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الإدخال؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">لا توجد إدخالات بعد.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
