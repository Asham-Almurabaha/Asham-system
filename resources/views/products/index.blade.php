@extends('layouts.master')

@section('title', 'قائمة المنتجات')

@section('content')

<div class="pagetitle">
    <h1>قائمة المنتجات</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Setting</li>
            <li class="breadcrumb-item active">Products</li>
        </ol>
    </nav>
</div><!-- End Page Title -->

<div class="card d-inline-block">
    <div class="card-body p-20">
        <a href="{{ route('products.create') }}" class="btn btn-success">إضافة منتج جديد</a>
    </div>
</div>

<div class="col-lg-12">
    <div class="card">
        <div class="card-body p-20">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th class="col-1">#</th>
                        <th class="col-5">اسم المنتج</th>
                        <th class="col-4">الوصف</th>
                        <th class="col-2">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <th>{{ $loop->iteration }}</th>
                            <td class="text-start">{{ $product->name }}</td>
                            <td class="text-start">{{ $product->description }}</td>
                            <td>
                                <a href="{{ route('products.edit', $product->id) }}" class="btn btn-primary btn-sm me-1">تعديل</a>
                                <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا المنتج؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">لا توجد منتجات بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
