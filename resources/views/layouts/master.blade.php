<!DOCTYPE html>
@php 
  $isRtl = app()->getLocale() === 'ar'; 
@endphp
<html lang="{{ $isRtl ? 'ar' : 'en' }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
  @include('layouts.head')
</head>
<body>

  @if (Auth::check())
    <!-- Header -->
    <header id="header" class="header fixed-top d-flex align-items-center">
      @include('layouts.header')
    </header>

    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar">
      @include('layouts.sidebar')
    </aside>
  @endif

  <main id="main" class="main">
    {{-- فلاش: نجاح --}}
    @if (session('success'))
      <div class="alert alert-success alert-dismissible fade show shadow" role="alert" aria-live="polite" id="flash-success"
           style="position: fixed; top: 70px; {{ app()->getLocale()==='ar'?'left':'right' }}: 20px; z-index: 9999; min-width: 260px;">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
      </div>
    @endif

    {{-- فلاش: أخطاء --}}
    @if ($errors->any())
      <div class="alert alert-danger alert-dismissible fade show shadow" role="alert" aria-live="polite" id="flash-error"
           style="position: fixed; top: 70px; {{ app()->getLocale()==='ar'?'left':'right' }}: 20px; z-index: 9999; min-width: 260px;">
        <ul class="mb-0">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
      </div>
    @endif

    @yield('content')
  </main>

  @include('layouts.script')
</body>
</html>
