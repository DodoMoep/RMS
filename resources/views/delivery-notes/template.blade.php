<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Lieferschein - {{ $order->order_number }}</title>
    <style>
        @page {
            margin: 15mm;
        }
        @page {
            @bottom-right {
                content: "Seite " counter(page) " von " counter(pages);
                font-size: 8pt;
                font-family: 'DejaVu Sans', sans-serif;
            }
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            color: #000;
            line-height: 1.3;
        }
        .ownership-note {
            font-size: 8pt;
            font-style: italic;
            margin-bottom: 10px;
        }
        .header-title {
            text-align: left;
            margin-bottom: 15px;
        }
        .header-title h1 {
            margin: 5px 0;
            font-size: 18pt;
            font-weight: bold;
        }
        .top-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .recipient-side {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }
        .sender-side {
            display: table-cell;
            width: 50%;
            vertical-align: bottom;
            text-align: right;
            padding-left: 10px;
        }
        .sender-side img.logo {
            max-width: 8.6cm;
            margin-bottom: 10px;
        }
        .sender-info {
            font-size: 9pt;
            line-height: 1.4;
            text-align: center;
        }
        .recipient-box {
            min-height: 70px;
            padding: 8px;
        }
        .recipient-box div {
            margin: 2px 0;
        }
        .recipient-label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .date-line {
            text-align: right;
            margin-bottom: 15px;
        }
        .delivery-intro {
            margin-bottom: 10px;
            font-weight: bold;
        }
        .articles-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .articles-table th {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
            font-weight: bold;
            font-size: 9pt;
            background-color: #f0f0f0;
        }
        .articles-table td {
            border: 1px solid #000;
            padding: 5px;
            font-size: 9pt;
        }
        .articles-table td.item-name {
            text-align: left;
        }
        .articles-table td.item-quantity {
            text-align: center;
        }
        .remarks-section {
            margin-bottom: 20px;
        }
        .remarks-label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .remarks-box {
            border: 1px solid #000;
            min-height: 50px;
            padding: 8px;
        }
        .signature-section {
            margin-top: 25px;
            page-break-inside: avoid;
        }
        .signature-row {
            display: table;
            width: 100%;
        }
        .signature-cell {
            display: table-cell;
            width: 50%;
            padding: 0 10px;
        }
        .signature-label {
            font-size: 9pt;
            margin-bottom: 40px;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            margin-bottom: 3px;
        }
        .footer {
            margin-top: 25px;
            padding-top: 8px;
            border-top: 1px solid #999;
            text-align: center;
            font-size: 8pt;
            page-break-inside: avoid;
        }
        .footer p {
            margin: 1px 0;
        }
        .page-number {
            position: fixed;
            bottom: 10mm;
            right: 15mm;
            font-size: 8pt;
        }
        .page-number:before {
            content: "Seite " counter(page);
        }
    </style>
</head>
<body>
    <div class="top-section">
        <div class="recipient-side">
            <div class="header-title">
                <h1>Lieferschein</h1>
            </div>
            <div class="recipient-box">
                <div class="recipient-label">Empfänger</div>
                <div>{{ $order->customer_name }}</div>
                @if($order->customer_address)
                    <div>{!! nl2br(e($order->customer_address)) !!}</div>
                @endif
                @if($order->customer_phone)
                    <div>Tel: {{ $order->customer_phone }}</div>
                @endif
            </div>
        </div>
        <div class="sender-side">
            <img src="{{ public_path('assets/images/Logo_GranderheiderWeihnachtsbaeume_CMYK.png') }}" alt="Logo" class="logo">
        </div>
    </div>

    <div class="date-line">
        <strong>Datum:</strong> {{ now()->format('d.m.Y') }}
    </div>

    <div class="delivery-intro">
        Sie erhalten nachfolgende Artikel:
    </div>

    <table class="articles-table">
        <thead>
            <tr>
                <th style="width: 35%;">Artikel</th>
                <th style="width: 15%;">Menge</th>
                <th style="width: 50%;">Bemerkung</th>
            </tr>
        </thead>
        <tbody>
            @forelse($order->items as $item)
                <tr>
                    <td class="item-name">{{ $item->article_name }}</td>
                    <td class="item-quantity">{{ $item->quantity_packed > 0 ? $item->quantity_packed : $item->quantity_ordered }}</td>
                    <td>{{ $item->notes ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center; font-style: italic;">Keine Artikel</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="remarks-section">
        <div class="remarks-label">Bemerkungen:</div>
        <div class="remarks-box">
            {{ $order->notes ?? '' }}
        </div>
    </div>

    <div class="signature-section">
        <div class="signature-row">
            <div class="signature-cell">
                <div class="signature-label">Ware ausgeliefert durch (Name + Unterschrift)</div>
                <div class="signature-line"></div>
            </div>
            <div class="signature-cell">
                <div class="signature-label">Ware erhalten (Name + Unterschrift)</div>
                <div class="signature-line"></div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p><strong>Familie Rosenau</strong></p>
        <p>Rausdorferstr. 3 • 22946 Grande</p>
        <p>Tel 04154 81394 • info@rosenau-weihnachtsbaeume.de</p>
        <p style="margin-top: 8px; font-style: italic;">Die gelieferte Ware bleibt bis zur vollständigen Bezahlung Eigentum des Lieferanten.</p>
    </div>
</body>
</html>
