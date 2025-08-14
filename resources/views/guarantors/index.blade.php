@extends('layouts.master')

@section('title', 'قائمة الكفلاء')

@section('content')

<div class="pagetitle">
    <h1>قائمة الكفلاء</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">الكفلاء</li>
        </ol>
    </nav>
</div><!-- End Page Title -->

<div class="mb-3">
    <a href="{{ route('guarantors.create') }}" class="btn btn-success">إضافة كفيل جديد</a>
</div>

<div class="card">
    <div class="card-body p-3">
        <table class="table table-striped text-center">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الاسم</th>
                    <th>رقم الهوية الوطنية</th>
                    <th>الهاتف</th>
                    <th>البريد الإلكتروني</th>
                    <th>الجنسية</th>
                    <th>الوظيفة</th>
                    <th>العنوان</th>
                    <th>صورة الهوية</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($guarantors as $guarantor)
                <tr>
                    <td>{{ $loop->iteration + ($guarantors->currentPage() - 1) * $guarantors->perPage() }}</td>
                    <td>{{ $guarantor->name }}</td>
                    <td>{{ $guarantor->national_id ?? '-' }}</td>
                    <td>{{ $guarantor->phone ?? '-' }}</td>
                    <td>{{ $guarantor->email ?? '-' }}</td>
                    <td>{{ $guarantor->nationality ? $guarantor->nationality->name : '-' }}</td>
                    <td>{{ $guarantor->title ? $guarantor->title->name : '-' }}</td>
                    <td>{{ $guarantor->address ?? '-' }}</td>
                    <td>
                        @if($guarantor->id_card_image)
                            <a href="{{ asset('storage/' . $guarantor->id_card_image) }}" target="_blank">
                                <img src="{{ asset('storage/' . $guarantor->id_card_image) }}" alt="صورة الهوية" width="60" height="40" style="object-fit: cover;">
                            </a>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('guarantors.show', $guarantor->id) }}" class="btn btn-secondary btn-sm mb-1">عرض</a>
                        <a href="{{ route('guarantors.edit', $guarantor->id) }}" class="btn btn-primary btn-sm mb-1">تعديل</a>
                        <form action="{{ route('guarantors.destroy', $guarantor->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('هل أنت متأكد من حذف هذا الكفيل؟');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm mb-1">حذف</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center">لا توجد بيانات لعرضها.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3">
            {{ $guarantors->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@endsection
