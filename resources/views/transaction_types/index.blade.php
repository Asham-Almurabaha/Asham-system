@extends('layouts.master')

@section('title', 'قائمة أنواع العمليات الحسابية')

@section('content')

<div class="pagetitle">
    <h1>قائمة أنواع العمليات الحسابية</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Setting</li>
            <li class="breadcrumb-item active">Transaction Types</li>
        </ol>
    </nav>
</div><!-- End Page Title -->

<div class="card d-inline-block mb-3">
    <div class="card-body p-20">
        <a href="{{ route('transaction_types.create') }}" class="btn btn-success">إضافة نوع عملية جديدة</a>
    </div>
</div>

<div class="col-lg-12">
    <div class="card">
        <div class="card-body p-20">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col" class="col-1">#</th>
                        <th scope="col" >الاسم</th>
                        <th scope="col" class="col-2">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($types as $type)
                        <tr>
                            <th scope="row">{{ $loop->iteration }}</th>
                            <td class="text-start">{{ $type->name }}</td>
                            <td>
                                <a href="{{ route('transaction_types.edit', $type->id) }}" class="btn btn-primary btn-sm me-1">تعديل</a>
                                <form action="{{ route('transaction_types.destroy', $type->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف نوع العملية؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">لا توجد أنواع عمليات بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
