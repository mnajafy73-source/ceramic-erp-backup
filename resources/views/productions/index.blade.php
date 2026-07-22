@extends('layouts.app')

@section('title', 'لیست تولیدات')

@push('scripts')
<script>
    $(function() {
        $('.in-production-toggle').on('change', function() {
            let productId = $(this).data('id');
            $.ajax({
                url: '{{ route('products.toggle-in-production', ':id') }}'.replace(':id', productId),
                type: 'PATCH',
                data: { _token: '{{ csrf_token() }}' }
            });
        });
    });
</script>
@endpush

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-bold">
        <i class="fas fa-cogs me-1"></i> محصولات در حال تولید
    </div>
    <div class="card-body">
        <div class="row">
            @foreach($allProducts as $product)
            <div class="col-md-3 col-sm-4 col-6 mb-2">
                <div class="form-check form-switch">
                    <input class="form-check-input in-production-toggle" type="checkbox" 
                           data-id="{{ $product->id }}" 
                           {{ $product->in_production ? 'checked' : '' }}>
                    <label class="form-check-label">{{ $product->name }}</label>
                </div>
            </div>
            @endforeach
        </div>
        <small class="text-muted">با فعال‌سازی هر محصول، در فرم ثبت تولید فقط همین محصولات نمایش داده می‌شوند.</small>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">تولیدات</h4>
    <a href="{{ route('productions.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> ثبت تولید جدید
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('productions.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="جستجو بر اساس محصول یا اپراتور..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">جستجو</button>
            </div>
            @if(request('search'))
            <div class="col-md-2">
                <a href="{{ route('productions.index') }}" class="btn btn-outline-secondary w-100">پاک کردن</a>
            </div>
            @endif
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>تاریخ</th>
                        <th>محصول</th>
                        <th>اپراتور</th>
                        <th>پرس</th>
                        <th>عملیات</th>
                        <th>تعداد</th>
                        <th>زمان (ساعت)</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productions as $production)
                        <tr>
                            <td>{{ \Morilog\Jalali\Jalalian::fromCarbon($production->date)->format('Y/m/d') }}</td>
                            <td>{{ $production->product->name ?? 'بدون محصول' }}</td>
                            <td>{{ $production->operator->name ?? 'بدون اپراتور' }}</td>
                            <td>{{ $production->press->name ?? 'بدون پرس' }}</td>
                            <td>
                                @if($production->stage == 'production') تولید
                                @elseif($production->stage == 'payment') پرداخت
                                @else بسته‌بندی
                                @endif
                            </td>
                            <td>{{ rtrim(rtrim(number_format($production->quantity, 2, '.', ''), '0'), '.') }}</td>
                            <td>{{ $production->time_hours !== null ? rtrim(rtrim(number_format($production->time_hours, 2, '.', ''), '0'), '.') : '—' }}</td>
                            <td class="d-flex gap-1">
                                <a href="{{ route('productions.show', $production) }}" class="btn btn-sm btn-outline-info" title="مشاهده"><i class="fas fa-eye"></i></a>
                                <a href="{{ route('productions.edit', $production) }}" class="btn btn-sm btn-outline-warning" title="ویرایش"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('productions.destroy', $production) }}" method="POST" onsubmit="return confirm('مطمئن هستید؟')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">هیچ تولیدی یافت نشد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    {{ $productions->links() }}
</div>
@endsection