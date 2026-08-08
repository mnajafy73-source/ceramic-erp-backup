@extends('layouts.app')

@section('title', 'جزئیات کالا')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">{{ $product->name }}</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('products.index') }}">کالاها</a></li>
            <li class="breadcrumb-item active">جزئیات</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <table class="table table-borderless">
            <tr><th>کد:</th><td><code>{{ $product->code }}</code></td></tr>
            <tr><th>نام:</th><td>{{ $product->name }}</td></tr>
            <tr><th>واحد:</th><td>{{ $product->unit->name ?? '—' }}</td></tr>
            <tr><th>موجودی اولیه:</th><td>{{ $product->initial_stock }}</td></tr>
            <tr><th>نوع کوره:</th>
                <td>
                    @if($product->kiln_type == 'tonneli') تونلی
                    @elseif($product->kiln_type == 'shuttle') شاتل
                    @else هر دو
                    @endif
                </td>
            </tr>
{{-- <tr><th>فرآیند پخت:</th><td>{{ $product->firing_process }}</td></tr> --}}            <tr><th>خوراک تونلی:</th><td>{{ $product->tonneli_feed_rate ?? '—' }} عدد/ساعت</td></tr>
            <tr><th>حفره:</th><td>{{ $product->cavities }}</td></tr>
            <tr><th>کارتن:</th><td>{{ $product->per_box ?? '—' }}</td></tr>
            <tr><th>بسته:</th><td>{{ $product->per_pack ?? '—' }}</td></tr>
            <tr><th>پالت:</th><td>{{ $product->per_pallet ?? '—' }}</td></tr>
            <tr><th>نوع کارتن:</th><td>{{ $product->box_type ?: '—' }}</td></tr>
            <tr><th>لایه/کارتن:</th><td>{{ $product->layers_per_box ?? '—' }}</td></tr>
            <tr><th>وضعیت:</th><td><span class="badge bg-{{ $product->status ? 'success' : 'danger' }}">{{ $product->status ? 'فعال' : 'غیرفعال' }}</span></td></tr>
            <tr><th>توضیحات:</th><td>{{ $product->description ?: '—' }}</td></tr>
        </table>
        <a href="{{ route('products.index') }}" class="btn btn-secondary">بازگشت</a>
        <a href="{{ route('products.edit', $product) }}" class="btn btn-warning ms-2">ویرایش</a>
    </div>
</div>

{{-- تاریخچه --}}
@if($product->logs->isNotEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold">تاریخچه تغییرات</div>
    <div class="card-body p-0">
        <ul class="list-group list-group-flush">
            @foreach($product->logs as $log)
                <li class="list-group-item">
                    <small class="text-muted">{{ optional($log->user)->name ?? 'ناشناس' }} — {{ $log->created_at->format('Y-m-d H:i') }}</small><br>
                    <strong>
                        @if($log->action == 'create') ایجاد
                        @elseif($log->action == 'update') ویرایش
                        @elseif($log->action == 'delete') حذف
                        @else {{ $log->action }}
                        @endif
                    </strong>
                    @if($log->changes)
                        @php $changes = json_decode($log->changes, true); @endphp
                        @if($changes)
                            <ul class="mb-0 mt-1">
                                @foreach($changes as $field => $value)
                                    <li>{{ $field }}: {{ is_array($value) ? json_encode($value) : $value }}</li>
                                @endforeach
                            </ul>
                        @endif
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endif
@endsection