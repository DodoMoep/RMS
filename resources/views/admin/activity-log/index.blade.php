<x-app-layout>
    {{-- Filter --}}
    <form method="get" class="d-flex gap-2 flex-wrap mb-3">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Suchen…" class="form-control form-control-sm" style="width:200px;">

        <select name="log_name" class="form-select form-select-sm" style="width:150px;">
            <option value="">Alle Logs</option>
            @foreach($logNames as $name)
                <option value="{{ $name }}" {{ request('log_name') === $name ? 'selected' : '' }}>
                    {{ $name }}
                </option>
            @endforeach
        </select>

        <select name="causer_id" class="form-select form-select-sm" style="width:170px;">
            <option value="">Alle Benutzer</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" {{ request('causer_id') == $user->id ? 'selected' : '' }}>
                    {{ $user->name }}
                </option>
            @endforeach
        </select>

        <button class="btn btn-outline-secondary btn-sm">Filtern</button>
        <a href="{{ route('activity-log.index') }}" class="btn btn-outline-secondary btn-sm">Zurücksetzen</a>

        <span class="ms-auto text-muted small align-self-center">{{ $logs->total() }} Einträge</span>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th style="width:140px;">Zeitpunkt</th>
                        <th style="width:100px;">Log</th>
                        <th style="width:120px;">Benutzer</th>
                        <th>Beschreibung</th>
                        <th>Objekt</th>
                        <th style="width:36px;"></th>
                    </tr>
                </thead>
                @forelse($logs as $log)
                    @php $hasProps = $log->properties && $log->properties->isNotEmpty(); @endphp
                    <tbody>
                        <tr>
                            <td class="text-nowrap text-muted">
                                {{ $log->created_at->format('d.m.Y H:i:s') }}
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $log->log_name }}</span>
                            </td>
                            <td>{{ $log->causer?->name ?? '—' }}</td>
                            <td>{{ $log->description }}</td>
                            <td class="text-muted">
                                @if($log->subject_type)
                                    {{ class_basename($log->subject_type) }}
                                    @if($log->subject_id)
                                        <span class="text-muted">#{{ Str::limit($log->subject_id, 8, '') }}</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($hasProps)
                                    <button class="btn btn-link btn-sm p-0"
                                            onclick="toggleLog(this, 'log-{{ $log->id }}')"
                                            title="Details">
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @if($hasProps)
                            <tr id="log-{{ $log->id }}" style="display:none;">
                                <td colspan="6" class="bg-light py-2 px-3">
                                    <pre class="mb-0 small" style="white-space:pre-wrap;">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="6" class="text-muted text-center py-4">Keine Einträge gefunden.</td>
                        </tr>
                    </tbody>
                @endforelse
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $logs->links('pagination::bootstrap-5') }}</div>

    @push('scripts')
    <script>
    function toggleLog(btn, rowId) {
        const row = document.getElementById(rowId);
        const icon = btn.querySelector('i');
        const isOpen = row.style.display !== 'none';
        row.style.display = isOpen ? 'none' : 'table-row';
        icon.className = isOpen ? 'fas fa-chevron-down' : 'fas fa-chevron-up';
    }
    </script>
    @endpush
</x-app-layout>
