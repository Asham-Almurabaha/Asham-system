@extends('layouts.master')

@section('title', 'قائمة المستثمرين')

@section('content')

<div class="pagetitle">
    <h1>قائمة المستثمرين</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">المستثمرين</li>
        </ol>
    </nav>
</div><!-- End Page Title -->

<div class="mb-3">
    <a href="{{ route('investors.create') }}" class="btn btn-success">إضافة مستثمر جديد</a>
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
                    <th>العنوان</th>
                    <th>الوظيفة</th>
                    <th>صورة الهوية</th>
                    <th>صورة العقد</th>
                    <th>نسبة المكتب %</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($investors as $investor)
                <tr>
                    <td>{{ $loop->iteration + ($investors->currentPage() - 1) * $investors->perPage() }}</td>
                    <td>{{ $investor->name }}</td>
                    <td>{{ $investor->national_id ?? '-' }}</td>
                    <td>{{ $investor->phone ?? '-' }}</td>
                    <td>{{ $investor->email ?? '-' }}</td>
                    <td>{{ $investor->nationality ? $investor->nationality->name : '-' }}</td>
                    <td>{{ $investor->address ?? '-' }}</td>
                    <td>{{ $investor->title ? $investor->title->name : '-' }}</td>
                    <td>
                        @if($investor->id_card_image)
                            <a href="{{ asset('storage/' . $investor->id_card_image) }}" target="_blank">
                                <img src="{{ asset('storage/' . $investor->id_card_image) }}" alt="صورة الهوية" width="60" height="40" style="object-fit: cover;">
                            </a>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($investor->contract_image)
                            <a href="{{ asset('storage/' . $investor->contract_image) }}" target="_blank">
                                <img src="{{ asset('storage/' . $investor->contract_image) }}" alt="صورة العقد" width="60" height="40" style="object-fit: cover;">
                            </a>
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $investor->office_share_percentage ?? '0' }}%</td>
                    <td>
                        <a href="{{ route('investors.show', $investor->id) }}" class="btn btn-secondary btn-sm mb-1">عرض</a>
                        <a href="{{ route('investors.edit', $investor->id) }}" class="btn btn-primary btn-sm mb-1">تعديل</a>
                        <form action="{{ route('investors.destroy', $investor->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('هل أنت متأكد من حذف هذا المستثمر؟');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm mb-1">حذف</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" class="text-center">لا توجد بيانات لعرضها.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3">
            {{ $investors->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@endsection
