@php
  $appName   = $setting->name ?? config('app.name', 'لوحة التحكم');
  $pageTitle = trim($__env->yieldContent('title'));              // عنوان الصفحة من @section('title')
  $title     = $pageTitle ? ($appName.' - '.$pageTitle) : $appName;

  $favicon   = $setting->favicon ?? null;
  $desc      = $setting->meta_description ?? $appName;           // إن عندك وصف في الإعدادات
  $canonical = request()->url();
  $locale    = app()->getLocale();
  $isRtl     = $locale === 'ar';
@endphp

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

@php
  $appName = app()->getLocale()==='ar' ? ($setting->name_ar ?? ($setting->name ?? $appName)) : ($setting->name_en ?? ($setting->name ?? $appName));
  $title   = $pageTitle ? ($appName.' - '.$pageTitle) : $appName;
  $desc    = $setting->meta_description ?? $appName;
@endphp

<title>{{ $title }}</title>

<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="{{ $desc }}">
<link rel="canonical" href="{{ $canonical }}">
<meta name="color-scheme" content="light dark">

{{-- Open Graph (للمشاركة على السوشيال) --}}
<meta property="og:locale" content="{{ $locale === 'ar' ? 'ar_AR' : 'en_US' }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $desc }}">
<meta property="og:site_name" content="{{ $appName }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:type" content="website">

{{-- Twitter Card --}}
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $desc }}">

{{-- Favicons --}}
@if ($favicon)
  <link rel="icon" href="{{ asset('storage/'.$favicon) }}" type="image/x-icon">
@else
  <link rel="icon" href="{{ asset('assets/img/favicon.png') }}" type="image/x-icon">
@endif
<link rel="apple-touch-icon" href="{{ asset('assets/img/apple-touch-icon.png') }}">

{{-- Google Fonts (روابط مباشرة مع دعم عربي محسن) --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link
  href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&family=Open+Sans:wght@300;400;600;700&family=Nunito:wght@300;400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap"
  rel="stylesheet">

{{-- Bootstrap & DataTables RTL/LTR --}}
@if ($isRtl)
  <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.rtl.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/datatables/css/dataTables.bootstrap5.rtl.min.css') }}" rel="stylesheet">
@else
  <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/datatables/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
@endif

{{-- Vendor CSS --}}
<link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">

{{-- Project utilities and custom styles (placed before template CSS to avoid overriding Bootstrap defaults) --}}
<link href="{{ asset('assets/css/util.css') }}" rel="stylesheet">
<link href="{{ asset('assets/css/custom.css') }}" rel="stylesheet">

{{-- Template Main CSS --}}
@if ($isRtl)
  <link href="{{ asset('assets/css/style.rtl.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/css/framework.rtl.css') }}" rel="stylesheet">
@else
  <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/css/framework.css') }}" rel="stylesheet">
@endif

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">

{{-- مناطق تخصيص إضافية للصفحات --}}
@yield('css')
@stack('css')
@stack('styles')
@stack('head')
