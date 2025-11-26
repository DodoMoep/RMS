<x-app-layout>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>{{ __('packing.pack_order') }} - {{ $order->order_number }}</h4>
        <a href="{{ route('packing.index') }}" class="btn btn-secondary btn-sm">{{ __('packing.back_to_overview') }}</a>
    </div>

    @if(session('ok'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('ok') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $totalItems = $order->items->count();
        $packedItems = $order->items->where('is_packed', true)->count();
        $percentage = $totalItems > 0 ? ($packedItems / $totalItems) * 100 : 0;
    @endphp

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-1">{{ __('orders.customer_name') }}: {{ $order->customer_name }}</h5>
                    <p class="text-muted mb-0 small">{{ __('orders.created_by') }}: {{ $order->creator->name }}</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="text-muted mb-1 small">{{ __('packing.progress') }}</p>
                    <h3 class="mb-0 text-primary">{{ $packedItems }} / {{ $totalItems }}</h3>
                </div>
            </div>
            <div class="progress mt-3" style="height: 25px;">
                <div class="progress-bar" role="progressbar" style="width: {{ $percentage }}%" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">{{ round($percentage) }}%</div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h5 class="card-title">{{ __('packing.checklist') }}</h5>
            <div class="vstack gap-3">
                @foreach($order->items as $item)
                    <div class="card {{ $item->is_packed ? 'border-success bg-light' : '' }}">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-7">
                                    <div class="d-flex align-items-start">
                                        @if($item->is_packed)
                                            <i class="fas fa-check-circle text-success fs-4 me-2"></i>
                                        @else
                                            <i class="far fa-circle text-muted fs-4 me-2"></i>
                                        @endif
                                        <div>
                                            <h6 class="mb-0 {{ $item->is_packed ? 'text-success' : '' }}">{{ $item->article_name }}</h6>
                                            <small class="{{ $item->is_packed ? 'text-success' : 'text-muted' }}">
                                                {{ __('orders.sku') }}: {{ $item->article_sku ?? 'N/A' }} |
                                                {{ __('orders.quantity') }}: {{ $item->quantity_packed }}/{{ $item->quantity_ordered }}
                                            </small>
                                            @if($item->notes)
                                                <div class="mt-1"><strong>{{ __('orders.notes') }}:</strong> {{ $item->notes }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5 text-end">
                                    @if(!$item->is_packed)
                                        <form action="{{ route('packing.pack-item', $item) }}" method="POST" class="d-inline">
                                            @csrf
                                            @if($item->quantity_ordered > 1)
                                                <input type="number" name="quantity" min="1" max="{{ $item->remainingQuantity() }}" value="{{ $item->remainingQuantity() }}" class="form-control form-control-sm d-inline-block me-2" style="width:80px;">
                                            @endif
                                            <button class="btn btn-success btn-sm">
                                                {{ $item->quantity_ordered > 1 ? __('packing.pack_partial') : __('packing.packed') }}
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('packing.unpack-item', $item) }}" method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-warning btn-sm">{{ __('packing.undo') }}</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

</x-app-layout>
