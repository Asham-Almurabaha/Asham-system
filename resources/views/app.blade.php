<!DOCTYPE html>
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
@endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $companyName }}</title>
    @if($isRtl)
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    @else
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @endif
    <!-- Additional CSS files -->
    <link rel="shortcut icon" href="{{ asset('storage/logos/' . basename($companyLogo)) }}" type="image/x-icon" />


</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('home') }}">{{ $companyName }}</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="@lang('app.Toggle navigation')">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="{{ route('customers.index') }}">@lang('app.Customers')</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('guarantors.index') }}">@lang('app.Guarantors')</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('investors.index') }}">@lang('app.Investors')</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('contracts.index') }}">@lang('app.Contracts')</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('installments.index') }}">@lang('app.Installments')</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('payments.index') }}">@lang('app.Payments')</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('settings.index') }}">@lang('app.Settings')</a></li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            @lang('app.Language')
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
                            <li><a class="dropdown-item" href="{{ route('lang.switch', 'en') }}">@lang('app.English')</a></li>
                            <li><a class="dropdown-item" href="{{ route('lang.switch', 'ar') }}">@lang('app.Arabic')</a></li>
                        </ul>
                    </li>
                    @auth
                        <li class="nav-item"><a class="nav-link" href="#">{{ auth()->user()->name }}</a></li>
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-link nav-link p-0" style="display:inline;">@lang('app.Logout')</button>
                            </form>
                        </li>
                    @else
                        <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">@lang('app.Login')</a></li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        @yield('content')
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- يمكن إضافة ملفات جافاسكريبت أخرى هنا -->
</body>
</html>
