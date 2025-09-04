<x-app-layout>
    <div class="row g-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Heute – Vermietungen</h5>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>Mieter:in</th><th>Halle</th><th>Start</th><th>Ende</th><th>Status</th><th>Aktion</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($todayRentals as $r)
                                <tr>
                                    <td>{{ $r->tenant->name }}</td>
                                    <td>{{ $r->hall->name }}</td>
                                    <td>{{ $r->start->format('d.m. H:i') }}</td>
                                    <td>{{ $r->end->format('d.m. H:i') }}</td>
                                    <td><span class="badge text-bg-secondary">{{ $r->status }}</span></td>
                                    <td class="text-nowrap">
                                        <form method="post" action="{{ route('rentals.handover',$r) }}" class="d-inline">@csrf
                                            <button class="btn btn-link btn-sm">Übergabe</button>
                                        </form>
                                        <form method="post" action="{{ route('rentals.return',$r) }}" class="d-inline">@csrf
                                            <button class="btn btn-link btn-sm">Rücknahme</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted">Keine Einträge.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Protokolle ohne PDF (offen)</h5>
                    <ul class="mb-0">
                        @forelse($openProtocols as $p)
                            <li class="mb-1">
                                {{ $p->type==='handover'?'Übergabe':'Rücknahme' }} – {{ $p->rental->tenant->name }} / {{ $p->rental->hall->name }}
                                <a class="ms-2" href="{{ route('protocol.form',$p) }}">öffnen</a>
                            </li>
                        @empty
                            <li class="text-muted">Keine offenen Protokolle.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
