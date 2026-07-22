@extends('layouts.app')

@section('title', 'لیست کالاها')

@push('scripts')
<script>
    $(function() {
        $(document).on('change', '.status-toggle', function() {
            let productId = $(this).data('id');
            let checkbox = $(this);
            let row = checkbox.closest('tr');
            let badge = row.find('.status-badge');

            $.ajax({
                url: '{{ route('products.toggle-status', ':id') }}'.replace(':id', productId),
                type: 'PATCH',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        if (response.status) {
                            badge.removeClass('bg-danger').addClass('bg-success').text('فعال');
                        } else {
                            badge.removeClass('bg-success').addClass('bg-danger').text('غیرفعال');
                        }
                    }
                },
                error: function() {
                    checkbox.prop('checked', !checkbox.prop('checked'));
                    alert('خطا در تغییر وضعیت. لطفاً دوباره تلاش کنید.');
                }
            });
        });
    });
</script>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">کالاها</h4>
    <a href="{{ route('products.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> کالای جدید
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('products.index') }}" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="جستجو بر اساس کد یا نام..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">جستجو</button>
            </div>
            @if(request('search'))
            <div class="col-md-2">
                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary w-100">پاک کردن</a>
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
                        <th>کد</th>
                        <th>نام کالا</th>
                        <th>واحد</th>
                        <th>نوع کوره</th>
                        <th>فرآیند پخت</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td><code>{{ $product->code }}</code></td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->unit->name ?? '-' }}</td>
                            <td>
                                @if($product->kiln_type == 'tonneli') تونلی
                                @elseif($product->kiln_type == 'shuttle') شاتل
                                @else هر دو
                                @endif
                            </td>
                            <td>{{ $product->firing_process == 'standard' ? 'معمولی' : 'چندمرحله‌ای' }}</td>
                            <td>
                                <div class="form-check form-switch d-inline-flex align-items-center gap-2">
                                    <input class="form-check-input status-toggle" type="checkbox" 
                                           data-id="{{ $product->id }}" 
                                           {{ $product->status ? 'checked' : '' }}>
                                    <span class="badge status-badge {{ $product->status ? 'bg-success' : 'bg-danger' }}">
                                        {{ $product->status ? 'فعال' : 'غیرفعال' }}
                                    </span>
                                </div>
                            </td>
                            <td class="d-flex gap-1">
                                <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-outline-info" title="مشاهده"><i class="fas fa-eye"></i></a>
                                <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-warning" title="ویرایش"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('products.destroy', $product) }}" method="POST" onsubmit="return confirm('آیا از حذف این کالا اطمینان دارید؟ در صورت وجود تاریخچه، حذف امکان‌پذیر نیست.')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">هیچ کالایی یافت نشد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    {{ $products->links() }}
</div>
@endsection