@extends('layouts.master')

@section('title', 'قائمة الحسابات البنكية والخزائن')

@section('content')

<div class="pagetitle">
    <h1>قائمة الحسابات البنكية والخزائن</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">الإعدادات</li>
            <li class="breadcrumb-item active">الحسابات</li>
        </ol>
    </nav>
</div><!-- End Page Title -->

<div class="card d-inline-block mb-3">
    <div class="card-body p-20">
        <a href="{{ route('bank_cash_accounts.create') }}" class="btn btn-success">إضافة حساب جديد</a>
    </div>
</div>

<div class="col-lg-12">
    <div class="card">
        <div class="card-body p-20">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th class="col-1">#</th>
                        <th class="col-2">الاسم</th>
                        <th class="col-2">النوع</th>
                        <th class="col-2">رقم الحساب</th>
                        <th class="col-1">الفرع</th>
                        <th class="col-1">الرصيد</th>
                        <th class="col-1">الحالة</th>
                        <th class="col-2">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $account)
                    <tr>
                        <th scope="row">{{ $loop->iteration }}</th>
                        <td>{{ $account->name }}</td>
                        <td>{{ ucfirst($account->type) }}</td>
                        <td>{{ $account->account_number ?? '-' }}</td>
                        <td>{{ $account->branch ?? '-' }}</td>
                        <td>{{ number_format($account->balance, 2) }}</td>
                        <td>
                            @if($account->active)
                                <span class="badge bg-success">نشط</span>
                            @else
                                <span class="badge bg-secondary">غير نشط</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('bank_cash_accounts.edit', $account->id) }}" class="btn btn-primary btn-sm me-1">تعديل</a>

                            <form action="{{ route('bank_cash_accounts.destroy', $account->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الحساب؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center">لا توجد حسابات حتى الآن.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
