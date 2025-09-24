@extends('layouts.master')

@section('title', 'دفتر القيود - إعداد مطلوب')

@section('content')
<div class="pagetitle mb-3">
    <h1 class="h3 mb-1">دفتر القيود</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">@lang('sidebar.Accounts')</li>
            <li class="breadcrumb-item active">@lang('sidebar.Ledger')</li>
        </ol>
    </nav>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="alert alert-warning" role="alert">
            <h2 class="h5 mb-3">لم يتم العثور على جداول الدفتر المطلوبة</h2>
            <p class="mb-3">
                يبدو أن ترحيلات قاعدة البيانات الخاصة بوحدة دفتر القيود لم تُشغَّل بعد. من أجل استخدام هذه الصفحة
                يجب إنشاء الجداول التالية في قاعدة البيانات:
            </p>
            @if (!empty($missingTables))
                <ul class="mb-3">
                    @foreach ($missingTables as $table)
                        <li><code>{{ $table }}</code></li>
                    @endforeach
                </ul>
            @endif
            <p class="mb-2">شغّل أمر الترحيلات المعتاد:</p>
            <pre class="bg-light p-3 rounded border"><code>php artisan migrate</code></pre>
            <p class="mb-2">أو لتشغيل ترحيلات الوحدة فقط:</p>
            <pre class="bg-light p-3 rounded border"><code>php artisan module:migrate Ledger</code></pre>
            <p class="mb-0 text-muted">بعد اكتمال الترحيلات أعد تحميل الصفحة.</p>
        </div>
    </div>
</div>
@endsection
