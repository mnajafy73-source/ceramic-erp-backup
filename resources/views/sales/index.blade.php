@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="fw-bold mb-0">فاکتورهای فروش رسمی</h4>
    <div class="d-flex gap-2">
        @if(request('source') === 'imported')
            <form action="{{ route('sales.clear-imported') }}" method="POST" onsubmit="return confirm('همه فاکتورهای ایمپورتی پاک بشن؟')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-trash me-1"></i> پاک کردن ایمپورتی‌ها
                </button>
            </form>
        @endif
        @if(request('source') === 'manual')
            <form action="{{ route('sales.clear-manual') }}" method="POST" onsubmit="return confirm('همه فاکتورهای دستی پاک بشن؟ (موجودی برگردانده می‌شود)')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-trash me-1"></i> پاک کردن دستی‌ها
                </button>
            </form>
        @endif
        <a href="{{ route('sales.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> فاکتور جدید
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success d-flex justify-content-between align-items-center">
        <span>{{ session('success') }}</span>
        @if(session('undo_record'))
            <a href="{{ route('undo.restore') }}" class="btn btn-sm btn-warning">برگرداندن</a>
        @endif
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

{{-- ✅ فیلتر منبع --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <span class="fw-bold small text-muted me-2">
                <i class="fas fa-filter me-1"></i> منبع:
            </span>
            @php
                $currentSource = request('source', 'all');
                $sourceButtons = [
                    'all'      => ['label' => 'همه',           'color' => 'dark'],
                    'manual'   => ['label' => 'دستی',          'color' => 'primary'],
                    'imported' => ['label' => 'ایمپورت اکسل',  'color' => 'info'],
                ];
            @endphp
            @foreach($sourceButtons as $key => $cfg)
                <a href="{{ route('sales.index', array_merge(request()->except('source', 'page'), ['source' => $key])) }}"
                   class="btn btn-sm {{ $currentSource === $key ? 'btn-' . $cfg['color'] : 'btn-outline-' . $cfg['color'] }}">
                    {{ $cfg['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</div>

{{-- ✅ فیلتر وضعیت --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <span class="fw-bold small text-muted me-2">
                <i class="fas fa-tag me-1"></i> وضعیت:
            </span>
            @php
                $currentStatus = request('status', 'all');
                $statusButtons = [
                    'all'       => ['label' => 'همه',                'color' => 'dark',    'count' => $statusCounts['all']],
                    'pending'   => ['label' => 'در انتظار پرداخت',   'color' => 'warning', 'count' => $statusCounts['pending']],
                    'paid'      => ['label' => 'پرداخت شده',        'color' => 'success', 'count' => $statusCounts['paid']],
                    'cancelled' => ['label' => 'باطل شده',          'color' => 'danger',  'count' => $statusCounts['cancelled']],
                ];
            @endphp
            @foreach($statusButtons as $key => $cfg)
                <a href="{{ route('sales.index', array_merge(request()->except('status', 'page'), ['status' => $key])) }}"
                   class="btn btn-sm {{ $currentStatus === $key ? 'btn-' . $cfg['color'] : 'btn-outline-' . $cfg['color'] }}">
                    {{ $cfg['label'] }}
                    <span class="badge bg-light text-dark ms-1">{{ number_format($cfg['count']) }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>شماره فاکتور</th>
                        <th>تاریخ</th>
                        <th>مشتری</th>
                        <th>حواله</th>
                        <th>جمع کل</th>
                        <th>وضعیت</th>
                        <th>منبع</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                    <tr>
                        <td><span class="badge bg-primary">{{ $sale->invoice_number }}</span></td>
                        <td>{{ \Morilog\Jalali\Jalalian::fromCarbon($sale->date)->format('Y/m/d') }}</td>
                        <td>{{ $sale->customer_name }}</td>
                        <td>
                            @if($sale->invoice)
                                {{ $sale->invoice->display_number }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ number_format($sale->total_with_tax, 0) }}</td>
                        <td>
                            @php
                                $statusLabels = ['pending' => 'در انتظار پرداخت', 'paid' => 'پرداخت شده', 'cancelled' => 'باطل شده'];
                                $statusColors = ['pending' => 'warning', 'paid' => 'success', 'cancelled' => 'danger'];
                            @endphp
                            <span class="badge bg-{{ $statusColors[$sale->status] }}">
                                {{ $statusLabels[$sale->status] }}
                            </span>
                        </td>
                        <td>
                            @if($sale->is_imported)
                                <span class="badge bg-info">
                                    <i class="fas fa-file-excel me-1"></i> اکسل
                                </span>
                            @else
                                <span class="badge bg-primary">
                                    <i class="fas fa-hand-paper me-1"></i> دستی
                                </span>
                            @endif
                        </td>
                        <td class="d-flex gap-1 flex-wrap">
                            <a href="{{ route('sales.show', $sale) }}" class="btn btn-sm btn-outline-info" title="مشاهده"><i class="fas fa-eye"></i></a>

                            @if($sale->status == 'pending')
                                <a href="{{ route('sales.edit', $sale) }}" class="btn btn-sm btn-outline-warning" title="ویرایش"><i class="fas fa-edit"></i></a>
                            @endif

                            @if($sale->status != 'paid')
                                <form action="{{ route('sales.destroy', $sale) }}" method="POST" onsubmit="return confirm('مطمئن هستید این فاکتور حذف شود؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="fas fa-trash"></i></button>
                                </form>
                            @endif

                            @if($sale->status == 'pending')
                                <form action="{{ route('sales.mark-paid', $sale) }}" method="POST">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-success">پرداخت شد</button>
                                </form>
                            @endif

                            @if($sale->status != 'cancelled')
                                <form action="{{ route('sales.cancel', $sale) }}" method="POST" onsubmit="return confirm('با باطل کردن این فاکتور، موجودی به حالت قبل برمی‌گردد. ادامه می‌دهید؟')">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger">باطل کن</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                        هیچ فاکتوری یافت نشد.
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $sales->links() }}</div>
@endsection