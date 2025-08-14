@extends('layouts.master')

@section('title', 'تعديل بيانات المستثمر')

@section('content')

<div class="pagetitle">
    <h1>تعديل بيانات المستثمر</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">المستثمرين</li>
            <li class="breadcrumb-item active">تعديل</li>
        </ol>
    </nav>
</div><!-- End Page Title -->

<div class="col-lg-8">
    <div class="card">
        <div class="card-body p-4">
            <form action="{{ route('investors.update', $investor->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label">الاسم <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $investor->name) }}" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="national_id" class="form-label">رقم الهوية الوطنية</label>
                    <input type="text" name="national_id" id="national_id" class="form-control" value="{{ old('national_id', $investor->national_id) }}">
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">الهاتف</label>
                    <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone', $investor->phone) }}">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">البريد الإلكتروني</label>
                    <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $investor->email) }}">
                </div>

                <div class="mb-3">
                    <label for="nationality_id" class="form-label">الجنسية</label>
                    <select name="nationality_id" id="nationality_id" class="form-select">
                        <option value="">-- اختر --</option>
                        @foreach ($nationalities as $nat)
                            <option value="{{ $nat->id }}" {{ old('nationality_id', $investor->nationality_id) == $nat->id ? 'selected' : '' }}>{{ $nat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="title_id" class="form-label">الوظيفة</label>
                    <select name="title_id" id="title_id" class="form-select">
                        <option value="">-- اختر --</option>
                        @foreach ($titles as $title)
                            <option value="{{ $title->id }}" {{ old('title_id', $investor->title_id) == $title->id ? 'selected' : '' }}>{{ $title->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">العنوان</label>
                    <textarea name="address" id="address" class="form-control" rows="3">{{ old('address', $investor->address) }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="id_card_image" class="form-label">صورة الهوية</label><br>
                    @if($investor->id_card_image)
                        <a href="{{ asset('storage/' . $investor->id_card_image) }}" target="_blank">
                            <img src="{{ asset('storage/' . $investor->id_card_image) }}" alt="صورة الهوية" width="100" style="object-fit: cover;">
                        </a>
                        <br><br>
                    @endif
                    <input type="file" name="id_card_image" id="id_card_image" class="form-control" accept="image/*">
                    <small class="text-muted">يمكنك رفع صورة جديدة لتغيير الصورة الحالية</small>
                </div>

                <div class="mb-3">
                    <label for="contract_image" class="form-label">صورة العقد</label><br>
                    @if($investor->contract_image)
                        <a href="{{ asset('storage/' . $investor->contract_image) }}" target="_blank">
                            <img src="{{ asset('storage/' . $investor->contract_image) }}" alt="صورة العقد" width="100" style="object-fit: cover;">
                        </a>
                        <br><br>
                    @endif
                    <input type="file" name="contract_image" id="contract_image" class="form-control" accept="image/*">
                    <small class="text-muted">يمكنك رفع صورة جديدة لتغيير الصورة الحالية</small>
                </div>

                <div class="mb-3">
                    <label for="office_share_percentage" class="form-label">نسبة المكتب (%)</label>
                    <input type="number" name="office_share_percentage" id="office_share_percentage" class="form-control" value="{{ old('office_share_percentage', $investor->office_share_percentage) }}" step="0.01" min="0" max="100" required>
                </div>

                <button type="submit" class="btn btn-primary">تحديث</button>
                <a href="{{ route('investors.index') }}" class="btn btn-secondary">إلغاء</a>
            </form>
        </div>
    </div>
</div>

@endsection
