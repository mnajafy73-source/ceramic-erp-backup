@extends('layouts.app')

@section('title', 'پخت‌های تونلی')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="fw-bold mb-0">پخت‌های کوره تونلی</h4>
    <div class="d-flex gap-2">
        @if(request('source') === 'imported')
            <form action="{{ route('tonneli.clear-imported') }}" method="POST" onsubmit="return confirm('همه رکوردهای ایمپورتی پاک بشن؟')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-trash me-1"></i> پاک کردن ایمپورتی‌ها
                </button>
            </form>
        @endif
        @if(request('source') === 'manual')
            <form action="{{ route('tonneli.clear-manual') }}" method="POST" onsubmit="return confirm('همه رکوردهای دستی پاک بشن؟ (موجودی برگردانده می‌شود)')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-trash me-1"></i> پاک کردن دستی‌ها
                </button>
            </form>
        @endif
        <a href="{{ route('tonneli.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> ثبت جدید
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
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
                <a href="{{ route('tonneli.index', array_merge(request()->except('source', 'page'), ['source' => $key])) }}"
                   class="btn btn-sm {{ $currentSource === $key ? 'btn-' . $cfg['color'] : 'btn-outline-' . $cfg['color'] }}">
                    {{ $cfg['label'] }}
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
                        <th>تاریخ</th>
                        <th>محصولات</th>
                        <th>منبع</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($firings as $firing)
                    <tr>
                        <td>{{ $firing->jalali_date ?? '—' }}</td>
                        <td>
                            @foreach($firing->items as $item)
                                <span class="badge bg-secondary">{{ $item->product->name ?? '—' }}</span>
                            @endforeach
                        </td>
                        <td>
                            @if($firing->is_imported)
                                <span class="badge bg-info">
                                    <i class="fas fa-file-excel me-1"></i> اکسل
                                </span>
                            @else
                                <span class="badge bg-primary">
                                    <i class="fas fa-hand-paper me-1"></i> دستی
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ url('/tonneli/'.$firing->id) }}" class="btn btn-sm btn-outline-info" title="مشاهده">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ url('/tonneli/'.$firing->id.'/edit') }}" class="btn btn-sm btn-outline-warning" title="ویرایش">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('tonneli.destroy', $firing->id) }}" method="POST" onsubmit="return confirm('مطمئن هستید؟')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="حذف">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                            هیچ رکوردی یافت نشد.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $firings->links() }}</div>
@endsection