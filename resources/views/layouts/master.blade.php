<!DOCTYPE html>
@php
  $locale     = app()->getLocale();
  $localeRoot = strtolower(strtok($locale, '_'));
  $rtlLocales = ['ar', 'he'];
  $isRtl      = in_array($localeRoot, $rtlLocales, true);
@endphp
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
  @include('layouts.head')
</head>
<body>

  @if (Auth::check())
    <!-- Header -->
    <header id="header" class="header fixed-top d-flex align-items-center pl-20">
      @include('layouts.header')
    </header>

    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar">
      @include('layouts.sidebar')
    </aside>
  @endif

  <main id="main" class="main">
    <x-flash-messages floating />

    @yield('content')
  </main>

  @include('layouts.script')
</body>
</html>
