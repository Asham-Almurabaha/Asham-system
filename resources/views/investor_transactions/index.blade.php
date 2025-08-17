@extends('layouts.master')

@section('title', 'عمليات المستثمرين')

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">عمليات المستثمرين</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">عمليات المستثمرين</li>
        </ol>
    </nav>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-center p-2">
        <a href="{{ route('investor-transactions.create') }}" class="btn btn-success">
            + إضافة عملية
        </a>

        <span class="ms-auto small text-muted">
            النتائج: <strong>{{ $transactions->total() }}</strong>
        </span>

        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#filterBar" aria-expanded="false">
            تصفية متقدمة
        </button>
    </div>

    <div class="collapse @if(request()->hasAny(['investor','status','from','to'])) show @endif border-top" id="filterBar">
        <div class="card-body">
            <form action="{{ route('investor-transactions.index') }}" method="GET" class="row gy-2 gx-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label mb-1">المستثمر</label>
                    <select name="investor" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        @foreach($investors as $inv)
                            <option value="{{ $inv->id }}" @selected(request('investor') == $inv->id)>{{ $inv->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">الحالة</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        @foreach($statuses as $st)
                            <option value="{{ $st->id }}" @selected(request('status') == $st->id)>{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">من تاريخ</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">إلى تاريخ</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-md-1 d-flex gap-2">
                    <button class="btn btn-primary btn-sm w-100">بحث</button>
                    <a href="{{ route('investor-transactions.index') }}" class="btn btn-outline-secondary btn-sm w-100">مسح</a>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px">#</th>
                        <th>المستثمر</th>
                        <th>الحالة</th>
                        <th>المبلغ</th>
                        <th>تاريخ العملية</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td class="text-muted">{{ $loop->iteration + ($transactions->currentPage() - 1) * $transactions->perPage() }}</td>
                            <td>{{ $transaction->investor->name ?? '-' }}</td>
                            <td>{{ $transaction->status->name ?? '-' }}</td>
                            <td>{{ number_format($transaction->amount, 2) }}</td>
                            <td>{{ $transaction->transaction_date }}</td>
                            <td>{{ $transaction->notes }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-5">
                                <div class="text-muted">
                                    لا توجد عمليات مطابقة لبحثك.
                                    <a href="{{ route('investor-transactions.index') }}" class="ms-1">عرض الكل</a>
                                </div>
                                <div class="mt-3">
                                    <a href="{{ route('investor-transactions.create') }}" class="btn btn-sm btn-success">
                                        + إضافة أول عملية
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($transactions->hasPages())
    <div class="card-footer bg-white">
        {{ $transactions->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>
@endsection