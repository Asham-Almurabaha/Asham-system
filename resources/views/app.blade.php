<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $companyName }}</title>
    <!-- Bootstrap RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <!-- يمكن إضافة ملفات CSS أخرى هنا -->
    <link rel="shortcut icon" href="{{ asset('storage/logos/' . basename($companyLogo)) }}" type="image/x-icon" />


</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('home') }}">نظام الأقساط</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="تبديل التنقل">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="{{ route('customers.index') }}">العملاء</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('guarantors.index') }}">الكفلاء</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('investors.index') }}">المستثمرين</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('contracts.index') }}">العقود</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('installments.index') }}">الأقساط</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('payments.index') }}">المدفوعات</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('settings.index') }}">الإعدادات</a></li>
                </ul>
                <ul class="navbar-nav">
                    @auth
                        <li class="nav-item"><a class="nav-link" href="#">{{ auth()->user()->name }}</a></li>
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-link nav-link p-0" style="display:inline;">تسجيل خروج</button>
                            </form>
                        </li>
                    @else
                        <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">تسجيل دخول</a></li>
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
