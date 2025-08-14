@extends('layouts.master')

@section('title', 'قائمة العناوين')

@section('content')

 <div class="pagetitle">
      <h1>قائمة العناوين</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">Setting</li>
          <li class="breadcrumb-item active">Title</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <div class="card d-inline-block">
        <div class="card-body p-20">
            <a href="{{ route('titles.create') }}" class="btn btn-success">إضافة وظيفة جديد</a>
        </div>
    </div>

    <div class="col-lg-12">
        <div class="card">
            <div class="card-body p-20">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th scope="col" class="col-1">#</th>
                    <th scope="col" class="col-9">الاسم</th>
                    <th scope="col" class="col-2">الإجراءات</th>
                </tr>
                </thead>
                <tbody>
                  @forelse($titles as $title)
                        <tr>
                            <th scope="row">{{ $loop->iteration }}</th>
                            <td class="text-start">{{ $title->name }}</td>
                            <td>
                                <a href="{{ route('titles.edit', $title->id) }}" class="btn btn-primary btn-sm me-1">تعديل</a>

                                <form action="{{ route('titles.destroy', $title->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الوظيفة؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">لا توجد عناوين بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
              </table>
            </div>
        </div>
    </div>
@endsection
