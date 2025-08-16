@extends('layouts.master')

@section('title', 'عمليات المستثمرين')

@section('content')
<div class="pagetitle">
    <h1>عمليات المستثمرين</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">عمليات المستثمرين</li>
        </ol>
    </nav>
</div>

<div class="mb-3">
    <a href="{{ route('investor-transactions.create') }}" class="btn btn-success">إضافة عملية</a>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>المستثمر</th>
                    <th>الحالة</th>
                    <th>المبلغ</th>
                    <th>تاريخ العملية</th>
                    <th>ملاحظات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $transaction)
                <tr>
                    <td>{{ $loop->iteration + ($transactions->currentPage() - 1) * $transactions->perPage() }}</td>
                    <td>{{ $transaction->investor->name ?? '-' }}</td>
                    <td>{{ $transaction->status->name ?? '-' }}</td>
                    <td>{{ number_format($transaction->amount, 2) }}</td>
                    <td>{{ $transaction->transaction_date }}</td>
                    <td>{{ $transaction->notes }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center">لا توجد عمليات</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{ $transactions->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection