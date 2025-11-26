<x-app-layout>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                <tr><th>Typ</th><th>Mieter:in</th><th>Halle</th><th>Erstellt</th><th>PDF</th><th class="text-end">Aktion</th></tr>
                </thead>
                <tbody>
                @forelse($protocols as $p)
                    <tr>
                        <td>{{ $p->type==='handover'?'Übergabe':'Rücknahme' }}</td>
                        <td>{{ $p->rental->tenant->name }}</td>
                        <td>{{ $p->rental->hall->name }}</td>
                        <td>{{ $p->created_at->format('d.m.Y H:i') }}</td>
                        <td>{!! $p->pdf_path ? '<span class="text-success">vorhanden</span>' : '<span class="text-muted">—</span>' !!}</td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-link btn-sm" href="{{ route('protocol.form',$p) }}">öffnen</a>
                            @if($p->pdf_path)
                                <a class="btn btn-link btn-sm" href="{{ route('protocol.pdf',$p) }}" target="_blank">PDF</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">Keine Einträge.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $protocols->links('pagination::bootstrap-5') }}</div>
</x-app-layout>
