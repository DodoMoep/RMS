<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Lieferschein - {{ $order->order_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .info-box {
            margin-bottom: 20px;
        }
        .info-box h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        .info-row {
            margin-bottom: 5px;
        }
        .info-row strong {
            display: inline-block;
            width: 150px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table thead {
            background-color: #f5f5f5;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table th {
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .signature-section {
            margin-top: 60px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 50%;
            padding: 20px;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 5px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LIEFERSCHEIN</h1>
        <p><strong>Lieferschein-Nr:</strong> {{ $order->order_number }}</p>
        <p><strong>Datum:</strong> {{ now()->format('d.m.Y') }}</p>
    </div>

    <div class="info-box">
        <h3>Lieferadresse</h3>
        <div class="info-row"><strong>Kunde:</strong> {{ $order->customer_name }}</div>
        @if($order->customer_email)
            <div class="info-row"><strong>E-Mail:</strong> {{ $order->customer_email }}</div>
        @endif
        @if($order->customer_phone)
            <div class="info-row"><strong>Telefon:</strong> {{ $order->customer_phone }}</div>
        @endif
        @if($order->customer_address)
            <div class="info-row"><strong>Adresse:</strong> {{ $order->customer_address }}</div>
        @endif
    </div>

    <div class="info-box">
        <h3>Bestelldetails</h3>
        <div class="info-row"><strong>Bestelldatum:</strong> {{ $order->created_at->format('d.m.Y H:i') }}</div>
        <div class="info-row"><strong>Verpackt von:</strong> {{ $order->packer->name ?? 'N/A' }}</div>
        <div class="info-row"><strong>Verpackt am:</strong> {{ $order->updated_at->format('d.m.Y H:i') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Pos.</th>
                <th>Artikel</th>
                <th>SKU</th>
                <th>Menge</th>
                <th>Hinweise</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->article_name }}</td>
                    <td>{{ $item->article_sku ?? '-' }}</td>
                    <td>{{ $item->quantity_ordered }}</td>
                    <td>{{ $item->notes ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($order->notes)
        <div class="info-box" style="margin-top: 20px;">
            <h3>Zusätzliche Hinweise</h3>
            <p>{{ $order->notes }}</p>
        </div>
    @endif

    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line">
                Unterschrift Lieferant
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                Unterschrift Empfänger
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Dieser Lieferschein wurde automatisch generiert am {{ now()->format('d.m.Y H:i') }}</p>
    </div>
</body>
</html>
