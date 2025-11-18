<x-app-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Bestellungs-Analytik</h4>
        @can('analytics.export')
            <a href="{{ route('analytics.export') }}" class="btn btn-success">
                <i class="fas fa-file-pdf me-1"></i> Bericht exportieren (PDF)
            </a>
        @endcan
    </div>
    <!-- Overview Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted small mb-2">Gesamt Bestellungen</h6>
                    <h2 class="mb-0">{{ $totalOrders }}</h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted small mb-2">Aktive Bestellungen</h6>
                    <h2 class="mb-0 text-warning">{{ $activeOrders }}</h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted small mb-2">Artikel verpackt (diesen Monat)</h6>
                    <h2 class="mb-0 text-success">{{ $itemsPacked }}</h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted small mb-2">Packer aktiv</h6>
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
                    <h5 class="card-title mb-3">Bestellungen nach Status</h5>
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
                    <h5 class="card-title mb-3">Top 10 Artikel</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Artikel</th>
                                    <th class="text-end">Menge</th>
                                    <th class="text-end">Bestellungen</th>
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
            <h5 class="card-title mb-3">Bestellungen über Zeit</h5>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Datum</th>
                            <th class="text-end">Anzahl</th>
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
            <h5 class="card-title mb-3">Packer-Leistung</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Packer</th>
                            <th class="text-end">Bestellungen verpackt</th>
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
                                    Keine Daten verfügbar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
