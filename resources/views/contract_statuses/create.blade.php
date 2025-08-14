@extends('layouts.master')

@section('title', 'إضافة حالة عقد جديدة')

@section('content')

    <div class="pagetitle">
      <h1>إضافة حالة عقد جديدة</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">Setting</li>
          <li class="breadcrumb-item">Contract Status</li>
          <li class="breadcrumb-item active">Add</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="col-lg-6">
        <div class="card">
            <div class="card-body p-20">
              <form action="{{ route('contract_statuses.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">اسم الحالة</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required autofocus>
                    </div>

                    <button type="submit" class="btn btn-success">حفظ</button>
                    <a href="{{ route('contract_statuses.index') }}" class="btn btn-secondary">إلغاء</a>
                </form>
            </div>
        </div>
    </div>

@endsection
