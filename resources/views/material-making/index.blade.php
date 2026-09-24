@extends('layouts.app')

@section('title', 'مواد سازی')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    .filter-bar {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        padding: 12px 16px;
        background: #fff;
        border-radius: 10px;
        border: 1px solid #e9ecef;
        margin-bottom: 16px;
        align-items: center;
    }
    .filter-bar .btn {
        border-radius: 20px;
        font-weight: 600;
        font-size: 13px;
        padding: 5px 16px;
    }

    #addMaterialModal .modal-header {
        background: linear-gradient(135deg, #198754, #20c997);
        color: #fff;
    }
    #addMaterialModal .modal-header .btn-close {
        filter: invert(1) brightness(2);
    }
    #addMaterialModal .form-label {
        font-weight: 600;
        font-size: 13px;
        color: #495057;
    }

    .datepicker-plot-area {
        font-family: Tahoma, sans-serif !important;
        z-index: 99999 !important;
    }
    .select2-container--open {
        z-index: 99999 !important;
    }
</style>
@endpush

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">📋 لیست مواد سازی</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">مواد سازی</li>
        </ol>
    </nav>
</div>

{{-- ✅ نوار فیلتر منبع --}}
<div class="filter-bar">
    <span class="fw-bold text-muted small me-2">
        <i class="fas fa-filter me-1"></i> فیلتر منبع:
    </span>

    <a href="{{ route('material-making.index', array_merge(request()->except('source', 'page'), ['source' => 'all'])) }}"
       class="btn btn-sm {{ $source === 'all' ? 'btn-dark' : 'btn-outline-dark' }}">
        <i class="fas fa-list"></i> همه
    </a>

    <a href="{{ route('material-making.index', array_merge(request()->except('source', 'page'), ['source' => 'manual'])) }}"
       class="btn btn-sm {{ $source === 'manual' ? 'btn-success' : 'btn-outline-success' }}">
        <i class="fas fa-hand-paper"></i> دستی
    </a>

    <a href="{{ route('material-making.index', array_merge(request()->except('source', 'page'), ['source' => 'imported'])) }}"
       class="btn btn-sm {{ $source === 'imported' ? 'btn-primary' : 'btn-outline-primary' }}">
        <i class="fas fa-file-excel"></i> اکسل
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <button type="button" class="btn btn-success"
                    data-bs-toggle="modal" data-bs-target="#addMaterialModal">
                <i class="fas fa-plus-circle me-1"></i> ثبت مواد سازی جدید
            </button>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-1"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
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

        @if($records->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
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
                            <th class="text-center">منبع</th>
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
                                <td>{{ number_format($record->mill_weight / 1000, 2) }}</td>

                                {{-- ✅ ستون منبع --}}
                                <td class="text-center">
                                    @if($record->is_imported)
                                        <span class="badge bg-primary">اکسل</span>
                                    @else
                                        <span class="badge bg-success">دستی</span>
                                    @endif
                                </td>

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
            <div class="alert alert-info">
                @if($source === 'manual')
                    هیچ رکورد دستی ثبت نشده است.
                @elseif($source === 'imported')
                    هیچ رکورد ایمپورتی (اکسل) وجود ندارد.
                @else
                    هیچ رکوردی ثبت نشده است.
                @endif
            </div>
        @endif
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{--  ✅ مدال ثبت مواد سازی جدید                                --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="addMaterialModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>
                    ثبت مواد سازی جدید
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form action="{{ route('material-making.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">تاریخ <span class="text-danger">*</span></label>
                            <input type="text" name="date" id="date_input"
                                   class="form-control jalali-date-input"
                                   value="{{ old('date', \Morilog\Jalali\Jalalian::now()->format('Y/m/d')) }}"
                                   autocomplete="off" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">نام (اختیاری)</label>
                            <input type="text" name="name" class="form-control"
                                   value="{{ old('name') }}"
                                   placeholder="مثلاً: بالمیل ۱">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">فرمول <span class="text-danger">*</span></label>
                            <select name="material" id="material_select" class="form-select" required>
                                <option value="">— انتخاب فرمول —</option>
                                @foreach($formulas as $f)
                                    <option value="{{ $f->name }}" {{ old('material') == $f->name ? 'selected' : '' }}>
                                        {{ $f->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">تعداد بالمیل <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="quantity" class="form-control"
                                   value="{{ old('quantity') }}" min="0.01" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">وزن بالمیل (کیلوگرم) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="mill_weight" class="form-control"
                                   value="{{ old('mill_weight') }}" min="0.01" required>
                        </div>

                    </div>

                    <div class="alert alert-info mt-3 mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        با ثبت این رکورد، مواد اولیه به‌طور خودکار از انبار کسر می‌شود.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> انصراف
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> ثبت
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script>
    $(document).ready(function() {
        // Select2 برای فرمول
        if ($.fn.select2) {
            $('#material_select').select2({
                placeholder: 'جستجو و انتخاب فرمول...',
                allowClear: true,
                width: '100%',
                dir: 'rtl',
                dropdownParent: $('#addMaterialModal')
            });
        }

        // Date picker
        if (typeof $.fn.pDatepicker !== 'undefined') {
            $('#date_input').pDatepicker({
                format: 'YYYY/MM/DD',
                initialValue: false,
                autoClose: true,
                persianDigit: false,
                observer: true,
                calendar: {
                    persian: { locale: 'fa', leapYearMode: 'algorithmic' }
                },
                toolbox: { calendarSwitch: { enabled: false } },
                navigator: { scroll: { enabled: true } },
                timePicker: { enabled: false }
            });
        }

        // اگه خطای اعتبارسنجی داشتیم، مدال رو باز کن
        @if($errors->any())
            new bootstrap.Modal(document.getElementById('addMaterialModal')).show();
        @endif
    });
</script>
@endpush