<!doctype html>
<html><head>
    <meta charset="utf-8">
    <style>
        body{ font-family: DejaVu Sans, sans-serif; font-size:12px; }
        h1{ font-size:18px; margin:0 0 10px; }
        table{ width:100%; border-collapse: collapse; margin-top:8px; }
        th,td{ border:1px solid #999; padding:6px; vertical-align: top; }
        .sig{ height:70px; }
        small{ color:#555; }
    </style>
</head><body>
<h1>{{ $protocol->type==='handover'?'Übergabeprotokoll':'Rücknahmeprotokoll' }}</h1>
<p><strong>Halle:</strong> {{ $protocol->rental->hall->name }} ({{ $protocol->rental->hall->address }})<br>
    <strong>Mieter:</strong> {{ $protocol->rental->tenant->name }} – {{ $protocol->rental->tenant->email }}<br>
    <strong>Zeitraum:</strong> {{ $protocol->rental->start->format('d.m.Y H:i') }} – {{ $protocol->rental->end->format('d.m.Y H:i') }}</p>

<table>
    <tr><th>Position</th><th>Status</th><th>Kommentar</th><th>Gebühr (€)</th></tr>
    @foreach($protocol->items as $i)
        <tr>
            <td>{{ $i->label }}</td>
            <td>{{ strtoupper($i->state) }}</td>
            <td>{{ $i->comment }}</td>
            <td>@if($i->charge) {{ number_format($i->charge,2,',','.') }} @endif</td>
        </tr>
    @endforeach
</table>

<p><strong>Checkliste</strong><br>
    Stromzähler: {{ data_get($protocol->checklist,'stromzaehler','-') }},
    Wasserzähler: {{ data_get($protocol->checklist,'wasserzaehler','-') }}</p>

<p><strong>Notizen:</strong><br>{{ $protocol->notes }}</p>

<table>
    <tr>
        @foreach($protocol->signatures as $sig)
            <td>
                <div>{{ $sig->role==='tenant'?'Mieter:in':'Vermieter:in' }}: {{ $sig->signer_name }}</div>

                @php $src = $signDataUris[$sig->id] ?? null; @endphp
                @if($src)
                    <img class="sig" src="{{ $src }}">
                @else
                    <div style="height:70px;border:1px solid #ccc;"></div>
                @endif

                <div>Signiert am: {{ $sig->signed_at->format('d.m.Y H:i') }}</div>
            </td>
        @endforeach
    </tr>
</table>

@if($protocol->photos->count())
    <p><strong>Fotos (Auszug)</strong></p>
    <table><tr>
            @foreach($protocol->photos->take(4) as $ph)
                @php $src = $photoDataUris[$ph->id] ?? null; @endphp
                <td>
                    @if($src)
                        <img src="{{ $src }}" style="max-width:180px; max-height:120px">
                    @else
                        <div style="width:180px;height:120px;border:1px solid #ccc;"></div>
                    @endif
                </td>
            @endforeach
        </tr></table>
@endif

<hr>
<small>Dokument-Hash (SHA-256): {{ $protocol->pdf_sha256 }}</small>
</body></html>
