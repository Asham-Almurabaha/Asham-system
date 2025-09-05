@extends('layouts.master')

@section('title', __('Contract Details'))

@section('content')
<div class="pagetitle">
    <h1>عرض العقد</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Show') }}</li>
        </ol>
    </nav>
</div>

<!-- أزرار الإجراءات -->
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-right-circle me-1"></i> {{ __('Back to List') }}</a>

    @php
        $paidTotal = $contract->installments->sum('payment_amount');
    @endphp

    <!-- طباعة العقد -->
    @if($paidTotal == 0)
        <a href="{{ route('contracts.print', $contract->id) }}" class="btn btn-primary">
            <i class="bi bi-printer me-1"></i> {{ __('Print Contract') }}</a>
    @endif

    <!-- طباعة السدادات -->
    @if($paidTotal <= $contract->total_value - $contract->discount_amount)
        <a href="{{ route('contracts.paid', $contract->id) }}" class="btn btn-outline-success">
            <i class="bi bi-receipt me-1"></i> {{ __('Paid Report') }}</a>
    @endif

    <!-- طباعة المخالصة -->
    @if($paidTotal >= $contract->total_value - $contract->discount_amount )
        <a href="{{ route('contracts.closure', $contract->id) }}" class="btn btn-outline-primary">
            <i class="bi bi-file-earmark-check me-1"></i> {{ __('Closure Report') }}</a>
    @endif
    {{--
    <form action="{{ route('contracts.destroy', $contract) }}" method="POST" class="ms-auto"
          onsubmit="return confirm('هل أنت متأكد من حذف هذا العقد؟');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">حذف</button>
    </form>
    --}}
</div>

{{-- معلومات أساسية عن العقد --}}
@include('contracts.partials.basic-info', ['contract' => $contract])

{{-- المستثمرون --}}
@include('contracts.partials.investors', ['contract' => $contract])

{{-- الأقساط --}}
@include('contracts.partials.installments', ['contract' => $contract])

{{-- الصور --}}
@include('contracts.partials.images', ['contract' => $contract])
@endsection
