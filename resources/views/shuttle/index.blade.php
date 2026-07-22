@extends('layouts.app')

@push('scripts')
<script>
    $(function() {
        $(document).on('change', '.packaged-toggle', function() {
            let checkbox = $(this);
            let url = checkbox.data('url');
            let badge = checkbox.closest('td').find('.packaged-badge');

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
    <h4 class="fw-bold mb-0">پخت‌های شاتل</h4>
    <a href="{{ route('shuttle.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i> ثبت جدید</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<table class="table table-hover align-middle bg-white rounded shadow-sm">
    <thead class="table-light">
        <tr>
            <th>تاریخ</th>
            <th>محصول</th>
            <th>نوع کوره</th>
            <th>شماره پخت</th>
            <th>نوع پخت</th>
            <th>خروجی</th>
            <th>بسته‌بندی</th>
            <th>عملیات</th>
        </tr>
    </thead>
    <tbody>
        @forelse($firings as $f)
        @php
            $kilnLabels = ['kiln_1' => 'کوره ۱', 'kiln_2' => 'کوره ۲', 'kiln_3' => 'کوره ۳', 'packaging' => 'بسته‌بندی'];
            $subtypeLabels = ['mum' => 'موم (۹۰۰°)', 'glaze' => 'لعابدار'];
        @endphp
        <tr>
            <td>{{ $f->jalali_date ?? '—' }}</td>
            <td>{{ $f->product->name ?? '—' }}</td>
            <td>{{ $kilnLabels[$f->kiln_type] ?? '—' }}</td>
            <td>{{ $f->firing_number ?? '—' }}</td>
            <td>{{ $f->firing_subtype ? $subtypeLabels[$f->firing_subtype] : '—' }}</td>
            <td>{{ $f->output_quantity !== null ? rtrim(rtrim(number_format($f->output_quantity, 2, '.', ''), '0'), '.') : '—' }}</td>
            <td>
                <div class="form-check form-switch">
                    <input class="form-check-input packaged-toggle" type="checkbox" 
                           data-url="{{ url('/shuttle/'.$f->id.'/toggle-packaged') }}"
                           {{ $f->is_packaged ? 'checked' : '' }}>
                    <span class="badge packaged-badge {{ $f->is_packaged ? 'bg-success' : 'bg-danger' }}">
                        {{ $f->is_packaged ? 'بله' : 'خیر' }}
                    </span>
                </div>
            </td>
            <td class="d-flex gap-1">
                <a href="{{ url('/shuttle/'.$f->id) }}" class="btn btn-sm btn-outline-info" title="مشاهده"><i class="fas fa-eye"></i></a>
                <a href="{{ url('/shuttle/'.$f->id.'/edit') }}" class="btn btn-sm btn-outline-warning" title="ویرایش"><i class="fas fa-edit"></i></a>
                <form action="{{ route('shuttle.destroy', $f->id) }}" method="POST" onsubmit="return confirm('مطمئن هستید؟')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="fas fa-trash"></i></button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="8" class="text-center">هیچ رکوردی یافت نشد.</td></tr>
        @endforelse
    </tbody>
</table>
<div class="mt-3">{{ $firings->links() }}</div>
@endsection