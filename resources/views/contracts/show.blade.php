@extends('layouts.master')

@section('title', 'عرض العقد')

@section('content')
<div class="pagetitle">
    <h1>📄 عرض العقد</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('contracts.index') }}">العقود</a></li>
            <li class="breadcrumb-item active">عرض العقد</li>
        </ol>
    </nav>
</div>

{{-- أزرار الإجراء --}}
<div class="d-flex flex-wrap gap-2 mb-3">
    {{-- <a href="{{ route('contracts.edit', $contract) }}" class="btn btn-primary">✏️ تعديل</a> --}}
    <a href="{{ route('contracts.index') }}" class="btn btn-secondary">↩ @lang('app.Back')</a>
    @php
        $paidTotal = $contract->installments->sum('payment_amount'); 
    @endphp

    @if($paidTotal == 0)
        <a href="{{ route('contracts.print', $contract->id) }}" target="_blank" class="btn btn-primary">
            🖨 @lang('app.Print')
        </a>
    @endif

    @if($paidTotal <= $contract->total_value - $contract->discount_amount)
        <a href="{{ route('contracts.paid', $contract->id) }}" target="_blank" class="btn btn-success">
    💰      @lang('app.Print')
        </a>
    @endif

    @if($paidTotal >= $contract->total_value - $contract->discount_amount )
        <a href="{{ route('contracts.closure', $contract->id) }}" target="_blank" class="btn btn-success">
            ✅ @lang('app.Print')
        </a>
    @endif
    {{-- <form action="{{ route('contracts.destroy', $contract) }}" method="POST" class="ms-auto" 
          onsubmit="return confirm('⚠️ هل أنت متأكد من حذف هذا العقد؟');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">🗑 حذف</button>
    </form> --}}
</div>

{{-- بيانات أساسية --}}
@include('contracts.partials.basic-info', ['contract' => $contract])

{{-- المستثمرون --}}
@include('contracts.partials.investors', ['contract' => $contract])

{{-- الأقساط --}}
@include('contracts.partials.installments', ['contract' => $contract])

{{-- الصور --}}
@include('contracts.partials.images', ['contract' => $contract])
@endsection
