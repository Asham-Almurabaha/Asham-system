@extends('layouts.master')

@section('title', 'قائمة حالات العمليات')

@section('content')

<div class="pagetitle">
    <h1>قائمة حالات العمليات</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">الإعدادات</li>
            <li class="breadcrumb-item active">حالات العمليات</li>
        </ol>
    </nav>
</div><!-- End Page Title -->

<div class="card d-inline-block">
    <div class="card-body p-20">
        <a href="{{ route('transaction_statuses.create') }}" class="btn btn-success">إضافة حالة جديدة</a>
    </div>
</div>

<div class="col-lg-12">
    <div class="card">
        <div class="card-body p-20">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col" class="col-1">#</th>
                        <th scope="col" class="col-5">اسم الحالة</th>
                        <th scope="col" class="col-4">نوع العملية</th>
                        <th scope="col" class="col-2">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statuses as $status)
                    <tr>
                        <th scope="row">{{ $loop->iteration }}</th>
                        <td>{{ $status->name }}</td>
                        <td>{{ $status->transactionType->name ?? '-' }}</td>
                        <td>
                            <a href="{{ route('transaction_statuses.edit', $status->id) }}" class="btn btn-primary btn-sm me-1">تعديل</a>

                            <form action="{{ route('transaction_statuses.destroy', $status->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه الحالة؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center">لا توجد حالات بعد.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
