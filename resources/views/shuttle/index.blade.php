@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">لیست پخت‌های شاتل</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">پخت شاتل</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="mb-3">
            <a href="{{ route('shuttle.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> ثبت پخت جدید
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        {{-- خلاصه آماری --}}
        <div class="row mb-4">
            @foreach($summaryData as $key => $data)
                <div class="col-md-3 col-6 mb-2">
                    <div class="card bg-light">
                        <div class="card-body text-center py-2">
                            <h6 class="card-title mb-0">{{ $data['label'] }}</h6>
                            <span class="badge bg-primary">{{ $data['count'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>تاریخ</th>
                        <th>نوع کوره</th>
                        <th>شماره پخت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $index => $batch)
                    <tr>
                        <td>{{ $batches->firstItem() + $index }}</td>
                        <td>{{ \Morilog\Jalali\Jalalian::fromCarbon($batch->date)->format('Y/m/d') }}</td>
                        <td>{{ $kilnLabels[$batch->kiln_type] ?? $batch->kiln_type }}</td>
                        <td>{{ $batch->firing_number }}</td>
                        <td>
                            <a href="{{ route('shuttle.show', ['firingNumber' => $batch->firing_number, 'date' => $batch->date->format('Y-m-d'), 'kiln_type' => $batch->kiln_type]) }}" 
                               class="btn btn-sm btn-success">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('shuttle.edit', ['firingNumber' => $batch->firing_number, 'date' => $batch->date->format('Y-m-d'), 'kiln_type' => $batch->kiln_type]) }}" 
                               class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('shuttle.destroy-batch') }}" method="POST" class="d-inline" 
                                  onsubmit="return confirm('آیا از حذف این پخت مطمئن هستید؟')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="firing_number" value="{{ $batch->firing_number }}">
                                <input type="hidden" name="date" value="{{ $batch->date->format('Y-m-d') }}">
                                <input type="hidden" name="kiln_type" value="{{ $batch->kiln_type }}">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center">هیچ پخت شاتلی ثبت نشده است.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $batches->links() }}
        </div>
    </div>
</div>
@endsection