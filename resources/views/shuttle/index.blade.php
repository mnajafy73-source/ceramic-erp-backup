@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">لیست پخت‌های کوره شاتل</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">داشبورد</a></li>
            <li class="breadcrumb-item active">کوره شاتل</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- دکمه ثبت پخت جدید --}}
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <a href="{{ route('shuttle.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i> ثبت پخت جدید
            </a>
        </div>

        {{-- ===== دکمه‌های فیلتر بر اساس نوع کوره ===== --}}
        @if($allKilnCounts->count())
            <div class="row g-2 mb-3">
                {{-- دکمه "همه" --}}
                <div class="col-auto">
                    <a href="{{ route('shuttle.index') }}" 
                       class="badge {{ is_null($filterKiln) || $filterKiln === 'all' ? 'bg-dark' : 'bg-secondary' }} p-2 fs-6 text-decoration-none">
                        همه ({{ $allKilnCounts->sum() }})
                    </a>
                </div>

                @foreach($allKilnCounts as $kilnType => $count)
                    @php
                        $kilnDisplay = 'نامشخص';
                        if ($kilnType === 'packaging') {
                            $kilnDisplay = 'بسته‌بندی';
                        } elseif (str_starts_with($kilnType, 'kiln_')) {
                            $kilnDisplay = 'کوره ' . substr($kilnType, 5);
                        }
                        $isActive = ($filterKiln == $kilnType);
                    @endphp
                    <div class="col-auto">
                        <a href="{{ route('shuttle.index', ['kiln' => $kilnType]) }}" 
                           class="badge {{ $isActive ? 'bg-primary' : 'bg-secondary' }} p-2 fs-6 text-decoration-none">
                            {{ $kilnDisplay }}: {{ $count }} پخت
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- نمایش تعداد کل رکوردهای فیلترشده --}}
        @if($paginated->count())
            <div class="mb-2 text-muted small">
                نمایش {{ $paginated->firstItem() }} تا {{ $paginated->lastItem() }} از {{ $paginated->total() }} پخت
                @if($filterKiln && $filterKiln !== 'all')
                    (فیلتر شده بر اساس {{ $kilnDisplay ?? 'کوره انتخاب‌شده' }})
                @endif
            </div>
        @endif

        {{-- جدول --}}
        @if($paginated->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ردیف</th>
                            <th>شماره پخت</th>
                            <th>تاریخ</th>
                            <th>کوره</th>
                            <th>نوع پخت</th>
                            <th>تعداد محصولات</th>
                            <th>مجموع تعداد</th>
                            <th>وضعیت بسته‌بندی</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paginated as $index => $firing)
                            @php
                                $kilnDisplay = 'نامشخص';
                                if ($firing->kiln_type === 'packaging') {
                                    $kilnDisplay = 'بسته‌بندی';
                                } elseif (str_starts_with($firing->kiln_type, 'kiln_')) {
                                    $kilnDisplay = 'کوره ' . substr($firing->kiln_type, 5);
                                }

                                $firingTypeDisplay = 'معمولی';
                                if ($firing->kiln_type === 'kiln_2') {
                                    $firingTypeDisplay = '۱۳۰۰';
                                } elseif ($firing->kiln_type === 'kiln_3') {
                                    if ($firing->firing_subtype === 'glaze') {
                                        $firingTypeDisplay = 'لعابدار';
                                    } elseif ($firing->firing_subtype === 'mum') {
                                        $firingTypeDisplay = 'موم';
                                    }
                                }
                            @endphp
                            <tr>
                                <td>{{ $paginated->firstItem() + $index }}</td>
                                <td>{{ $firing->firing_number }}</td>
                                <td>{{ $firing->date }}</td>
                                <td><span class="badge bg-primary">{{ $kilnDisplay }}</span></td>
                                <td><span class="badge bg-info">{{ $firingTypeDisplay }}</span></td>
                                <td>{{ $firing->products_count }}</td>
                                <td>{{ number_format($firing->total_quantity) }}</td>
                                <td>
                                    @if($firing->is_packaged)
                                        <span class="badge bg-success">بله</span>
                                    @else
                                        <span class="badge bg-secondary">خیر</span>
                                    @endif
                                </td>
                                <td>
                                    {{-- دکمه مشاهده --}}
                                    <a href="{{ route('shuttle.show', [
                                        'year' => $firing->year,
                                        'month' => $firing->month,
                                        'day' => $firing->day,
                                        'kiln_type' => $firing->kiln_type,
                                        'firingNumber' => $firing->firing_number
                                    ]) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> مشاهده
                                    </a>

                                    {{-- دکمه ویرایش --}}
                                    <a href="{{ route('shuttle.edit', [
                                        'year' => $firing->year,
                                        'month' => $firing->month,
                                        'day' => $firing->day,
                                        'kiln_type' => $firing->kiln_type,
                                        'firingNumber' => $firing->firing_number
                                    ]) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> ویرایش
                                    </a>

                                    {{-- فرم حذف --}}
                                    <form action="{{ route('shuttle.destroy', [
                                        'year' => $firing->year,
                                        'month' => $firing->month,
                                        'day' => $firing->day,
                                        'kiln_type' => $firing->kiln_type,
                                        'firingNumber' => $firing->firing_number
                                    ]) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('آیا از حذف این پخت مطمئن هستید؟')">
                                            <i class="fas fa-trash"></i> حذف
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $paginated->links() }}
            </div>
        @else
            <div class="alert alert-info">
                @if($filterKiln && $filterKiln !== 'all')
                    هیچ پختی برای کوره انتخاب‌شده یافت نشد.
                @else
                    هیچ پخت شاتلی ثبت نشده است.
                @endif
            </div>
        @endif
    </div>
</div>
@endsection