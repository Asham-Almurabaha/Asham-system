<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <title>@yield('title')</title>
    <style>
        @page { size: A4 landscape; margin: 0; }
        html, body { margin: 0; padding: 0; width: 297mm; height: 210mm; }
    </style>
    @stack('styles')
</head>
<body>
    @yield('content')
    @stack('scripts')
</body>
</html>