<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bestellungs-Analytik Bericht</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
        }
        h1 {
            font-size: 24px;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        h2 {
            font-size: 18px;
            margin-top: 20px;
            margin-bottom: 10px;
            color: #34495e;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-primary { background-color: #3498db; color: white; }
        .badge-success { background-color: #27ae60; color: white; }
        .badge-warning { background-color: #f39c12; color: white; }
        .badge-danger { background-color: #e74c3c; color: white; }
        .badge-info { background-color: #16a085; color: white; }
        .stat-box {
            display: inline-block;
            width: 23%;
            margin-right: 2%;
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
        .stat-box:last-child {
            margin-right: 0;
        }
        .stat-label {
            font-size: 11px;
            color: #7f8c8d;
            text-transform: uppercase;
        }
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
            margin-top: 5px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #7f8c8d;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Bestellungs-Analytik Bericht</h1>
    <p style="color: #7f8c8d; margin-bottom: 20px;">Erstellt am: {{ $generatedAt->format('d.m.Y H:i') }} Uhr</p>

    <!-- Overview Statistics -->
    <div style="margin-bottom: 30px;">
        <div class="stat-box">
            <div class="stat-label">Gesamt Bestellungen</div>
            <div class="stat-value">{{ $totalOrders }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Status: Neu</div>
            <div class="stat-value" style="color: #3498db;">
                {{ $statusData->where('status.value', 'new')->first()->count ?? 0 }}
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Status: In Bearbeitung</div>
            <div class="stat-value" style="color: #f39c12;">
                {{ $statusData->where('status.value', 'in_progress')->first()->count ?? 0 }}
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Status: Verpackt</div>
            <div class="stat-value" style="color: #27ae60;">
                {{ $statusData->where('status.value', 'packed')->first()->count ?? 0 }}
            </div>
        </div>
    </div>

    <!-- Status Distribution -->
    <h2>Bestellungen nach Status</h2>
    <table>
        <thead>
            <tr>
                <th>Status</th>
                <th class="text-right">Anzahl</th>
                <th class="text-right">Prozent</th>
            </tr>
        </thead>
        <tbody>
            @foreach($statusData as $status)
                @php
                    $statusEnum = $status->status instanceof \App\Enums\OrderStatus 
                        ? $status->status 
                        : \App\Enums\OrderStatus::from($status->status);
                    $percentage = $totalOrders > 0 ? round(($status->count / $totalOrders) * 100, 1) : 0;
                @endphp
                <tr>
                    <td>{{ $statusEnum->label() }}</td>
                    <td class="text-right">{{ $status->count }}</td>
                    <td class="text-right">{{ $percentage }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Top Items -->
    <h2>Top 10 Artikel</h2>
    <table>
        <thead>
            <tr>
                <th>Artikel</th>
                <th class="text-right">Gesamtmenge</th>
                <th class="text-right">Anzahl Bestellungen</th>
            </tr>
        </thead>
        <tbody>
            @foreach($topItems as $item)
                <tr>
                    <td>{{ $item->article_name }}</td>
                    <td class="text-right">{{ $item->total_quantity }}</td>
                    <td class="text-right">{{ $item->order_count }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Packer Performance -->
    <h2>Packer-Leistung</h2>
    <table>
        <thead>
            <tr>
                <th>Packer</th>
                <th class="text-right">Bestellungen verpackt</th>
            </tr>
        </thead>
        <tbody>
            @forelse($packerPerformance as $packer)
                <tr>
                    <td>{{ $packer->packer_name }}</td>
                    <td class="text-right">
                        <span class="badge badge-success">{{ $packer->orders_packed }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" style="text-align: center; color: #7f8c8d;">
                        Keine Daten verfügbar.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Rosenau Management System - Bestellungs-Analytik</p>
    </div>
</body>
</html>
