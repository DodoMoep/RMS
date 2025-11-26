<x-app-layout>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>{{ __('orders.history') }} - {{ $order->order_number }}</h4>
        <a href="{{ route('orders.show', $order) }}" class="btn btn-secondary btn-sm">{{ __('orders.back_to_order') }}</a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">{{ __('orders.timeline') }}</h5>
                    
                    <div class="position-relative">
                        <div class="position-absolute" style="left: 1rem; top: 0; bottom: 0; width: 2px; background-color: #dee2e6;"></div>
                        
                        <div class="vstack gap-4">
                            @foreach($order->history as $history)
                                <div class="position-relative" style="padding-left: 3rem;">
                                    @php
                                        $bgColor = str_contains($history->event_type, 'created') ? 'bg-primary' :
                                                   (str_contains($history->event_type, 'status') ? 'bg-info' :
                                                   (str_contains($history->event_type, 'unpacked') ? 'bg-warning' :
                                                   (str_contains($history->event_type, 'packed') ? 'bg-success' :
                                                   (str_contains($history->event_type, 'deleted') ? 'bg-danger' : 'bg-secondary'))));
                                    @endphp
                                    
                                    <div class="position-absolute rounded-circle {{ $bgColor }} d-flex align-items-center justify-content-center text-white" 
                                         style="left: 0; top: 0.25rem; width: 2rem; height: 2rem;">
                                        @if(str_contains($history->event_type, 'created'))
                                            <i class="fas fa-plus-circle"></i>
                                        @elseif(str_contains($history->event_type, 'unpacked'))
                                            <i class="fas fa-undo"></i>
                                        @elseif(str_contains($history->event_type, 'packed'))
                                            <i class="fas fa-check-circle"></i>
                                        @elseif(str_contains($history->event_type, 'status'))
                                            <i class="fas fa-sync-alt"></i>
                                        @elseif(str_contains($history->event_type, 'deleted'))
                                            <i class="fas fa-times-circle"></i>
                                        @else
                                            <i class="fas fa-info-circle"></i>
                                        @endif
                                    </div>
                                    
                                    <div class="card bg-light border">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="mb-0">{{ $history->description }}</h6>
                                                <small class="text-muted">{{ $history->created_at->format('d.m.Y H:i') }}</small>
                                            </div>
                                            
                                            <div class="small text-muted">
                                                @if($history->user)
                                                    <div><strong>{{ __('orders.user') }}:</strong> {{ $history->user->name }}</div>
                                                @endif
                                                
                                                <div><strong>{{ __('orders.event_type') }}:</strong> <code class="badge bg-secondary">{{ $history->event_type }}</code></div>
                                                
                                                @if($history->metadata && count($history->metadata) > 0)
                                                    <div class="mt-2">
                                                        <strong>{{ __('orders.details') }}:</strong>
                                                        <div class="bg-white p-2 rounded border mt-1">
                                                            @foreach($history->metadata as $key => $value)
                                                                <div class="small">{{ $key }}: {{ is_array($value) ? json_encode($value) : $value }}</div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                                
                                                @if($history->ip_address)
                                                    <div class="mt-1 small text-black-50">IP: {{ $history->ip_address }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
