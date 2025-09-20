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
    <x-button.action href="{{ route('contracts.index') }}" variant="secondary" :outline="true">
                    <i class="bi bi-arrow-right-circle me-1"></i> {{ __('Back to List') }}</x-button.action>

    @php
        $paidTotal = $contract->installments->sum('payment_amount');
    @endphp

    <!-- طباعة العقد -->
    @if($paidTotal == 0)
        <x-button.action href="{{ route('contracts.print', $contract->id) }}" variant="primary" target="_blank" rel="noopener">
            <i class="bi bi-printer me-1"></i> {{ __('Print Contract') }}</x-button.action>
    @endif

    <!-- طباعة السدادات -->
    @if($paidTotal <= $contract->total_value - $contract->discount_amount)
        <x-button.action href="{{ route('contracts.paid', $contract->id) }}" variant="success" :outline="true" target="_blank" rel="noopener">
            <i class="bi bi-receipt me-1"></i> {{ __('Paid Report') }}</x-button.action>
    @endif

    <!-- طباعة المخالصة -->
    @if($paidTotal >= $contract->total_value - $contract->discount_amount )
        <x-button.action href="{{ route('contracts.closure', $contract->id) }}" variant="primary" :outline="true" target="_blank" rel="noopener">
            <i class="bi bi-file-earmark-check me-1"></i> {{ __('Closure Report') }}</x-button.action>
    @endif
    {{--
    <form action="{{ route('contracts.destroy', $contract) }}" method="POST" class="ms-auto"
          onsubmit="return confirm('هل أنت متأكد من حذف هذا العقد؟');">
        @csrf
        @method('DELETE')
        <x-button.action type="submit" variant="danger">حذف</x-button.action>
    </form>
    --}}
</div>

{{-- معلومات أساسية عن العقد --}}
@include('contracts::partials.basic-info', ['contract' => $contract])

{{-- ملاحظات العقد --}}
@include('contracts::partials.notes', ['contract' => $contract])

{{-- المستثمرون --}}
@include('contracts::partials.investors', ['contract' => $contract])

{{-- الأقساط --}}
@include('contracts::partials.installments', ['contract' => $contract])

{{-- المطالبات --}}
@include('contracts::partials.claims', [
    'contract' => $contract,
    'claimants' => $claimants,
    'claimStatuses' => $claimStatuses,
    'claimPayers' => $claimPayers,
    'changeStatusOptions' => $changeStatusOptions,
    'paidWithDiscountClaimStatusId' => $paidWithDiscountClaimStatusId,
    'banks' => $banks,
    'safes' => $safes,
])

{{-- الصور --}}
@include('contracts::partials.images', ['contract' => $contract])
@endsection
