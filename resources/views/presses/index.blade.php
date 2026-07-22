@extends('layouts.app')

@section('title', 'لیست پرس‌ها')

@push('scripts')
<script>
    $(function() {
        $(document).on('change', '.status-toggle', function() {
            let pressId = $(this).data('id');
            let checkbox = $(this);
            let row = checkbox.closest('tr');
            let badge = row.find('.status-badge');

            $.ajax({
                url: '{{ route('presses.toggle-status', ':id') }}'.replace(':id', pressId),
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
    <h4 class="fw-bold mb-0">پرس‌ها</h4>
    <a href="{{ route('presses.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> پرس جدید
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>نام</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($presses as $press)
                        <tr>
                            <td>{{ $press->id }}</td>
                            <td>{{ $press->name }}</td>
                            <td>
                                <div class="form-check form-switch d-inline-flex align-items-center gap-2">
                                    <input class="form-check-input status-toggle" type="checkbox" 
                                           data-id="{{ $press->id }}" 
                                           {{ $press->status ? 'checked' : '' }}>
                                    <span class="badge status-badge {{ $press->status ? 'bg-success' : 'bg-danger' }}">
                                        {{ $press->status ? 'فعال' : 'غیرفعال' }}
                                    </span>
                                </div>
                            </td>
                            <td class="d-flex gap-1">
                                <a href="{{ route('presses.edit', $press) }}" class="btn btn-sm btn-outline-warning" title="ویرایش">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('presses.destroy', $press) }}" method="POST" onsubmit="return confirm('مطمئن هستید؟')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="حذف">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">هیچ پرس یافت نشد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    {{ $presses->links() }}
</div>
@endsection