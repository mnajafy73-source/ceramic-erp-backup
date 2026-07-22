@extends('layouts.app')

@push('scripts')
<script>
    $(function() {
        // تغییر وضعیت بسته‌بندی با کلیک روی toggle
        $(document).on('change', '.packaged-toggle', function() {
            let checkbox = $(this);
            let url = checkbox.data('url');
            let badge = checkbox.closest('td').find('.packaged-badge'); // پیدا کردن badge مرتبط

            $.ajax({
                url: url,
                type: 'PATCH',
                data: { _token: '{{ csrf_token() }}' },
                success: function(r) {
                    if (r.success) {
                        if (r.is_packaged) {
                            badge.removeClass('bg-danger').addClass('bg-success').text('بله');
                        } else {
                            badge.removeClass('bg-success').addClass('bg-danger').text('خیر');
                        }
                    }
                }
            });
        });
    });
</script>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">پخت‌های کوره تونلی</h4>
    <a href="{{ url('/tonneli/create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i> ثبت جدید</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<table class="table table-hover align-middle bg-white rounded shadow-sm">
    <thead class="table-light">
        <tr>
            <th>تاریخ</th>
            <th>محصول</th>
            <th>ورودی</th>
            <th>خروجی</th>
            <th>بسته‌بندی</th>
            <th>عملیات</th>
        </tr>
    </thead>
    <tbody>
        @forelse($firings as $f)
        <tr>
            <td>{{ $f->jalali_date ?? '—' }}</td>
            <td>{{ $f->product->name ?? '—' }}</td>
            <td>{{ rtrim(rtrim(number_format($f->input_quantity, 2, '.', ''), '0'), '.') }}</td>
            <td>{{ rtrim(rtrim(number_format($f->output_quantity, 2, '.', ''), '0'), '.') }}</td>
            <td>
                <div class="form-check form-switch">
                    <input class="form-check-input packaged-toggle" type="checkbox" 
                           data-url="{{ url('/tonneli/'.$f->id.'/toggle-packaged') }}"
                           {{ $f->is_packaged ? 'checked' : '' }}>
                    <span class="badge packaged-badge {{ $f->is_packaged ? 'bg-success' : 'bg-danger' }}">
                        {{ $f->is_packaged ? 'بله' : 'خیر' }}
                    </span>
                </div>
            </td>
            <td class="d-flex gap-1">
                <a href="{{ url('/tonneli/'.$f->id) }}" class="btn btn-sm btn-outline-info" title="مشاهده"><i class="fas fa-eye"></i></a>
                <a href="{{ url('/tonneli/'.$f->id.'/edit') }}" class="btn btn-sm btn-outline-warning" title="ویرایش"><i class="fas fa-edit"></i></a>
                <form action="{{ url('/tonneli/'.$f->id) }}" method="POST" onsubmit="return confirm('مطمئن هستید؟')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="fas fa-trash"></i></button>
                </form>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="text-center">هیچ رکوردی یافت نشد.</td>
        </tr>
        @endforelse
    </tbody>
</table>
<div class="mt-3">{{ $firings->links() }}</div>
@endsection