@extends('layouts.master')

@section('title', 'إضافة كفيل جديد')

@section('content')

<div class="pagetitle">
    <h1>إضافة كفيل جديد</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">الكفلاء</li>
            <li class="breadcrumb-item active">إضافة</li>
        </ol>
    </nav>
</div><!-- End Page Title -->

<div class="col-lg-8">
    <div class="card">
        <div class="card-body p-4">
            <form action="{{ route('guarantors.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">الاسم <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="national_id" class="form-label">رقم الهوية الوطنية</label>
                    <input type="text" name="national_id" id="national_id" class="form-control" value="{{ old('national_id') }}">
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">الهاتف</label>
                    <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone') }}">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">البريد الإلكتروني</label>
                    <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}">
                </div>

                <div class="mb-3">
                    <label for="nationality_id" class="form-label">الجنسية</label>
                    <select name="nationality_id" id="nationality_id" class="form-select">
                        <option value="">-- اختر --</option>
                        @foreach ($nationalities as $nat)
                            <option value="{{ $nat->id }}" @selected(old('nationality_id') == $nat->id)>{{ $nat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="title_id" class="form-label">الوظيفة</label>
                    <select name="title_id" id="title_id" class="form-select">
                        <option value="">-- اختر --</option>
                        @foreach ($titles as $title)
                            <option value="{{ $title->id }}" @selected(old('title_id') == $title->id)>{{ $title->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">العنوان</label>
                    <textarea name="address" id="address" class="form-control" rows="3">{{ old('address') }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="id_card_image" class="form-label">صورة الهوية</label>
                    <input type="file" name="id_card_image" id="id_card_image" class="form-control" accept="image/*">
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">ملاحظات</label>
                    <textarea name="notes" id="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                </div>

                <button type="submit" class="btn btn-success">حفظ</button>
                <a href="{{ route('guarantors.index') }}" class="btn btn-secondary">إلغاء</a>
            </form>
        </div>
    </div>
</div>

@endsection
