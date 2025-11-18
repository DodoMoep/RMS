<x-app-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>{{ __('analytics.title') }}</h4>
        <div class="d-flex gap-2">
            <form method="GET" action="{{ route('analytics.index') }}" class="d-flex gap-2">
                <select name="period" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto;">
                    <option value="7" {{ request('period', 30) == 7 ? 'selected' : '' }}>{{ __('analytics.periods.last_7_days') }}</option>
                    <option value="30" {{ request('period', 30) == 30 ? 'selected' : '' }}>{{ __('analytics.periods.last_30_days') }}</option>
                    <option value="90" {{ request('period', 30) == 90 ? 'selected' : '' }}>{{ __('analytics.periods.last_90_days') }}</option>
                    <option value="365" {{ request('period', 30) == 365 ? 'selected' : '' }}>{{ __('analytics.periods.last_year') }}</option>
                    <option value="all" {{ request('period', 30) == 'all' ? 'selected' : '' }}>{{ __('analytics.periods.all_time') }}</option>
                </select>
            </form>
            @can('analytics.export')
                <a href="{{ route('analytics.export', ['period' => request('period', 30)]) }}" class="btn btn-success btn-sm">
                    <i class="fas fa-file-pdf me-1"></i> {{ __('analytics.export_report') }}
                </a>
            @endcan
        </div>
    </div>
    <!-- Overview Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted small mb-2">{{ __('analytics.total_orders') }}</h6>
                    <h2 class="mb-0">{{ $totalOrders }}</h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted small mb-2">{{ __('analytics.active_orders') }}</h6>
                    <h2 class="mb-0 text-warning">{{ $activeOrders }}</h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted small mb-2">{{ __('analytics.items_packed') }}</h6>
                    <h2 class="mb-0 text-success">{{ $itemsPacked }}</h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted small mb-2">{{ __('analytics.active_packers') }}</h6>
                    <h2 class="mb-0 text-primary">{{ $packerPerformance->count() }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Distribution -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">{{ __('analytics.orders_by_status') }}</h5>
                    <div class="vstack gap-3">
                        @foreach($statusData as $status)
                            @php
                                $statusEnum = $status->status instanceof \App\Enums\OrderStatus 
                                    ? $status->status 
                                    : \App\Enums\OrderStatus::from($status->status);
                                $percentage = $totalOrders > 0 ? ($status->count / $totalOrders) * 100 : 0;
                                $bgClass = str_replace(['bg-', '-100'], ['', ''], $statusEnum->color());
                            @endphp
                            <div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="small fw-medium">{{ $statusEnum->label() }}</span>
                                    <span class="small text-muted">{{ $status->count }}</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar {{ $bgClass }}" role="progressbar" style="width: {{ $percentage }}%" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">{{ __('analytics.top_articles') }}</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('orders.article') }}</th>
                                    <th class="text-end">{{ __('orders.quantity') }}</th>
                                    <th class="text-end">{{ __('orders.orders') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topItemsData as $item)
                                    <tr>
                                        <td class="small">{{ $item->article_name }}</td>
                                        <td class="small text-end">{{ $item->total_quantity }}</td>
                                        <td class="small text-end">{{ $item->order_count }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Time Series -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">{{ __('analytics.orders_over_time') }}</h5>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('common.dates.date') }}</th>
                            <th class="text-end">{{ __('analytics.count') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($timeSeriesData as $data)
                            <tr>
                                <td class="small">{{ $data->period }}</td>
                                <td class="small text-end">
                                    <span class="badge bg-primary">{{ $data->count }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Packer Performance -->
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3">{{ __('analytics.packer_performance') }}</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('analytics.packer') }}</th>
                            <th class="text-end">{{ __('analytics.orders_packed') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($packerPerformance as $packer)
                            <tr>
                                <td class="fw-medium">{{ $packer->packer_name }}</td>
                                <td class="text-end">
                                    <span class="badge bg-success">{{ $packer->orders_packed }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted">
                                    {{ __('analytics.no_data') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
