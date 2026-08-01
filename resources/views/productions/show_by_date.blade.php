@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">تولیدات روز {{ $jalaliDate }}</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('productions.index') }}">تولید</a></li>
            <li class="breadcrumb-item active">جزئیات روز</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>اپراتور</th>
                        <th>محصول</th>
                        <th>عملیات</th>
                        <th>پرس</th>
                        <th>تعداد</th>
                        <th>زمان (ساعت)</th>
                        <th>توقف‌ها</th>
                        <th class="text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productions as $production)
                    <tr>
                        <td>{{ $production->operator->name ?? '—' }}</td>
                        <td>{{ $production->product->name ?? '—' }}</td>
                        <td>
                            @php
                                $stageLabels = [
                                    'production' => 'تولید',
                                    'payment' => 'پرداخت',
                                    'packaging' => 'بسته‌بندی',
                                ];
                            @endphp
                            {{ $stageLabels[$production->stage] ?? $production->stage }}
                        </td>
                        <td>{{ $production->press->name ?? '—' }}</td>
                        <td>{{ number_format($production->quantity) }}</td>
                        <td>{{ $production->time_hours ? number_format($production->time_hours, 1) : '—' }}</td>
                        <td>
                            @foreach($production->stops as $stop)
                                <span class="badge bg-secondary">
                                    @if($stop->type == 'machine_failure') خرابی ماشین
                                    @elseif($stop->type == 'mold_change_repair') تعویض قالب
                                    @else {{ $stop->type }} @endif
                                    ({{ $stop->hours }} ساعت)
                                </span>
                            @endforeach
                        </td>
                        <td class="text-center">
                            <a href="{{ route('productions.edit', $production) }}" class="btn btn-sm btn-outline-warning" title="ویرایش">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('productions.destroy', $production) }}" method="POST" class="d-inline" onsubmit="return confirm('مطمئن هستید این رکورد حذف شود؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="حذف">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center">هیچ رکوردی برای این روز یافت نشد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="{{ route('productions.index') }}" class="btn btn-secondary">بازگشت</a>
</div>
@endsection