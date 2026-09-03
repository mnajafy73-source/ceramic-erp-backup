@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">📊 آمار فروش محصولات (ماهیانه)</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">داشبورد</a></li>
            <li class="breadcrumb-item active">آمار فروش محصولات</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0">افزودن محصول</h5>

        {{-- فرم افزودن محصول --}}
        <form action="{{ route('product-sales-stats.add') }}" method="POST" class="d-flex align-items-center gap-2 flex-wrap">
            @csrf
            <div class="form-group mb-0" style="min-width: 280px;">
                <select name="product_id" class="form-control product-search-select" style="width: 100%;" required>
                    <option value="">جستجو و انتخاب محصول...</option>
                    @foreach($allProductsList as $product)
                        <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->code }})</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> افزودن
            </button>
        </form>
    </div>

    <div class="card-body">
        {{-- فیلتر ماه و سال --}}
        <form method="GET" action="{{ route('product-sales-stats.index') }}" class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">سال</label>
                <select name="year" class="form-select">
                    @for($y = $currentYear - 2; $y <= $currentYear; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">ماه</label>
                <select name="month" class="form-select">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ $monthNames[$m - 1] }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">نمایش آمار</button>
            </div>
        </form>

        {{-- جدول اصلی با عملیات حذف و ستون‌های ساده --}}
        @if($reportData->count())
            <h5 class="fw-bold mt-4 mb-3">نتایج</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th rowspan="2" class="align-middle">عملیات</th>
                            <th rowspan="2" class="align-middle">نام محصول</th>
                            <th colspan="2" class="text-center">رسمی</th>
                            <th colspan="2" class="text-center">غیر رسمی</th>
                            <th colspan="2" class="text-center">مجموع</th>
                        </tr>
                        <tr>
                            <th class="text-center">تعداد</th>
                            <th class="text-center">مبلغ (ریال)</th>
                            <th class="text-center">تعداد</th>
                            <th class="text-center">مبلغ (ریال)</th>
                            <th class="text-center">تعداد</th>
                            <th class="text-center">مبلغ (ریال)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData as $item)
                            <tr>
                                <td>
                                    <form action="{{ route('product-sales-stats.remove') }}" method="POST" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $item->product_id }}">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('آیا از حذف این محصول از لیست آمار فروش مطمئن هستید؟')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </td>
                                <td>{{ $item->product_name }}</td>
                                <td class="text-center">{{ number_format($item->formal_total) }}</td>
                                <td class="text-center">{{ number_format($item->formal_amount) }}</td>
                                <td class="text-center">{{ number_format($item->informal_total) }}</td>
                                <td class="text-center">{{ number_format($item->informal_amount) }}</td>
                                <td class="text-center fw-bold">{{ number_format($item->total_quantity) }}</td>
                                <td class="text-center fw-bold">{{ number_format($item->total_amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-secondary fw-bold">
                        <tr>
                            <td colspan="2">مجموع</td>
                            <td class="text-center">{{ number_format($reportData->sum('formal_total')) }}</td>
                            <td class="text-center">{{ number_format($reportData->sum('formal_amount')) }}</td>
                            <td class="text-center">{{ number_format($reportData->sum('informal_total')) }}</td>
                            <td class="text-center">{{ number_format($reportData->sum('informal_amount')) }}</td>
                            <td class="text-center">{{ number_format($reportData->sum('total_quantity')) }}</td>
                            <td class="text-center">{{ number_format($reportData->sum('total_amount')) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="alert alert-info">
                هیچ محصولی به لیست آمار فروش اضافه نشده است. از قسمت بالا محصول مورد نظر را جستجو و اضافه کنید.
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.product-search-select').select2({
            placeholder: 'جستجو و انتخاب محصول...',
            allowClear: true,
            width: '100%',
            minimumInputLength: 0,
            language: {
                searching: function() {
                    return 'در حال جستجو...';
                },
                noResults: function() {
                    return 'محصولی یافت نشد';
                }
            }
        });
    });
</script>
@endpush