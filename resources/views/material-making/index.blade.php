@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">📋 لیست مواد سازی</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">مواد سازی</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <a href="{{ route('import.index') }}" class="btn btn-primary">
                <i class="fas fa-file-import me-1"></i> واردات از اکسل
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if($records->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ردیف</th>
                            <th>سال</th>
                            <th>ماه</th>
                            <th>روز</th>
                            <th>نام</th>
                            <th>فرمول</th>
                            <th>تعداد بالمیل</th>
                            <th>وزن بالمیل (کیلوگرم)</th>
                            {{-- ستون کل مواد (کیلوگرم) حذف شد --}}
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($records as $index => $record)
                            <tr>
                                <td>{{ $records->firstItem() + $index }}</td>
                                <td>{{ $record->year }}</td>
                                <td>{{ $record->month }}</td>
                                <td>{{ $record->day }}</td>
                                <td>{{ $record->name ?? '-' }}</td>
                                <td>{{ $record->material }}</td>
                                <td>{{ number_format($record->quantity) }}</td>
                                {{-- وزن بالمیل به کیلوگرم نمایش داده می‌شود (تقسیم بر ۱۰۰۰) --}}
                                <td>{{ number_format($record->mill_weight / 1000, 2) }}</td>
                                <td>
                                    <form action="{{ route('material-making.destroy', $record->id) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('آیا از حذف این رکورد مطمئن هستید؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $records->links() }}</div>
        @else
            <div class="alert alert-info">هیچ رکوردی ثبت نشده است.</div>
        @endif
    </div>
</div>
@endsection