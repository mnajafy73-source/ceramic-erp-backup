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
                                    <a href="{{ route('productions.show-by-date', ['date' => $group->date]) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> جزئیات
                                    </a>
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