@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">موجودی خام</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">موجودی</a></li>
            <li class="breadcrumb-item active">موجودی خام</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>نام محصول</th>
                        <th>موجودی خام (عدد)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inventories as $item)
                    <tr>
                        <td>{{ $item['product']->name }}</td>
                        <td>{{ number_format($item['stock']) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" class="text-center">هیچ محصولی یافت نشد.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection