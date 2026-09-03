@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">لیست تولیدات</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">تولیدات</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between mb-3">
            <a href="{{ route('productions.create') }}" class="btn btn-primary">ثبت تولید جدید</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($productions->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ردیف</th>
                            <th>تاریخ</th>
                            <th>تعداد رکورد</th>
                            <th>مجموع تعداد</th>
                            <th>اپراتورها</th>
                            <th>محصولات</th>
                            <th>عملیات‌ها</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($productions as $index => $group)
                            @php
                                $dateParts = explode('/', $group->date);
                                $year = $dateParts[0] ?? '';
                                $month = $dateParts[1] ?? '';
                                $day = $dateParts[2] ?? '';
                            @endphp
                            <tr>
                                <td>{{ $productions->firstItem() + $index }}</td>
                                <td>
                                    @php
                                        try {
                                            \Morilog\Jalali\Jalalian::fromFormat('Y/m/d', $group->date);
                                            $displayDate = $group->date;
                                        } catch (\Exception $e) {
                                            $displayDate = 'نامعتبر';
                                        }
                                    @endphp
                                    {{ $displayDate }}
                                </td>
                                <td>{{ number_format($group->total_rows) }}</td>
                                <td>{{ number_format($group->total_quantity) }}</td>
                                <td>{{ $group->operators_text }}</td>
                                <td>{{ $group->products_text }}</td>
                                <td>{{ $group->stages_text }}</td>
                                <td>
                                    {{-- ✅ اصلاح شده: نام صحیح route --}}
                                    <a href="{{ route('productions.by-date', ['date' => $group->date]) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> جزئیات
                                    </a>

                                    @if($year && $month && $day)
                                        <form action="{{ route('productions.destroy-group', ['year' => $year, 'month' => $month, 'day' => $day]) }}" method="POST" class="d-inline" 
                                              onsubmit="return confirm('آیا از حذف تمام تولیدات تاریخ {{ $group->date }} مطمئن هستید؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i> حذف گروه
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $productions->links() }}
            </div>
        @else
            <div class="alert alert-info">هیچ تولیدی ثبت نشده است.</div>
        @endif
    </div>
</div>
@endsection