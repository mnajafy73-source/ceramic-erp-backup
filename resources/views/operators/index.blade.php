@extends('layouts.app')

@section('title', 'لیست اپراتورها')

@push('scripts')
<script>
    $(function() {
        $(document).on('change', '.status-toggle', function() {
            let operatorId = $(this).data('id');
            let checkbox = $(this);
            let row = checkbox.closest('tr');
            let badge = row.find('.status-badge');

            $.ajax({
                url: '{{ route('operators.toggle-status', ':id') }}'.replace(':id', operatorId),
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
    <h4 class="fw-bold mb-0">اپراتورها</h4>
    <a href="{{ route('operators.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> اپراتور جدید
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
                    @forelse($operators as $operator)
                        <tr>
                            <td>{{ $operator->id }}</td>
                            <td>{{ $operator->name }}</td>
                            <td>
                                <div class="form-check form-switch d-inline-flex align-items-center gap-2">
                                    <input class="form-check-input status-toggle" type="checkbox" 
                                           data-id="{{ $operator->id }}" 
                                           {{ $operator->status ? 'checked' : '' }}>
                                    <span class="badge status-badge {{ $operator->status ? 'bg-success' : 'bg-danger' }}">
                                        {{ $operator->status ? 'فعال' : 'غیرفعال' }}
                                    </span>
                                </div>
                            </td>
                            <td class="d-flex gap-1">
                                <a href="{{ route('operators.edit', $operator) }}" class="btn btn-sm btn-outline-warning" title="ویرایش">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('operators.destroy', $operator) }}" method="POST" onsubmit="return confirm('مطمئن هستید؟')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="حذف">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">هیچ اپراتوری یافت نشد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    {{ $operators->links() }}
</div>
@endsection