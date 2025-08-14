@extends('layouts.master')

@section('title', 'تعديل الوظيفة')

@section('content')

    <div class="pagetitle">
      <h1>تعديل</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">Setting</li>
          <li class="breadcrumb-item">Title</li>
          <li class="breadcrumb-item active">Edit</li>
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
              <form action="{{ route('titles.update', $title->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label">اسم الوظيفة</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $title->name) }}" required autofocus>
                    </div>

                    <button type="submit" class="btn btn-primary">تحديث</button>
                    <a href="{{ route('titles.index') }}" class="btn btn-secondary">إلغاء</a>
                </form>
            </div>
        </div>
    </div>
@endsection
