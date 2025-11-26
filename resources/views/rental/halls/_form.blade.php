@csrf
<div class="vstack gap-3">
    <div>
        <x-input-label for="name" value="Name"/>
        <x-text-input id="name" name="name" :value="old('name', $hall->name ?? '')" required />
        <x-input-error :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="address" value="Adresse"/>
        <x-text-input id="address" name="address" :value="old('address', $hall->address ?? '')" />
        <x-input-error :messages="$errors->get('address')" />
    </div>

    <div>
        <x-input-label for="notes" value="Notizen"/>
        <textarea id="notes" name="notes" class="form-control" rows="3">{{ old('notes', $hall->notes ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('notes')" />
    </div>

    {{-- INVENTAR-ZUORDNUNG --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h6 class="card-title mb-3">Inventar der Halle</h6>

            @php
                // Pivot-Mengen für Edit bequem greifbar machen:
                $existing = isset($hall)
                  ? $hall->inventory->pluck('pivot.quantity','id')->toArray()
                  : [];
            @endphp

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th style="width:38px;"></th>
                        <th>Artikel</th>
                        <th>SKU</th>
                        <th style="width:140px;">Menge</th>
                    </tr>
                    </thead>
                    <tbody id="inv-table">
                    @forelse($items as $it)
                        @php
                            $checked = array_key_exists($it->id, $existing) || old("inventory.$it->id.selected");
                            $qtyOld  = old("inventory.$it->id.quantity");
                            $qty     = $qtyOld !== null ? $qtyOld : ($existing[$it->id] ?? '');
                        @endphp
                        <tr>
                            <td>
                                <input type="checkbox"
                                       class="form-check-input inv-toggle"
                                       id="inv_{{ $it->id }}"
                                       name="inventory[{{ $it->id }}][selected]"
                                       value="1"
                                    @checked($checked) >
                            </td>
                            <td>
                                <label class="mb-0" for="inv_{{ $it->id }}">{{ $it->name }}</label>
                            </td>
                            <td class="text-muted">{{ $it->sku }}</td>
                            <td>
                                <input type="number" min="1" class="form-control form-control-sm inv-qty"
                                       name="inventory[{{ $it->id }}][quantity]"
                                       value="{{ $qty }}"
                                       @if(!$checked) disabled @endif
                                       placeholder="Stück">
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">Noch keine Inventar-Artikel angelegt.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="form-text">Häkchen setzen, Menge eintragen. Ungesetzte Positionen werden entfernt.</div>
            <x-input-error :messages="$errors->get('inventory.*.quantity')" />
        </div>
    </div>
</div>

<div class="mt-3 d-flex gap-2">
    <x-primary-button>Speichern</x-primary-button>
    <x-secondary-button onclick="history.back();return false;">Abbrechen</x-secondary-button>
</div>

@push('scripts')
    <script>
        (function() {
            // Init + Umschalten: arbeitet sicher auch, wenn DOM schon da ist
            function wireRow(cb) {
                const row = cb.closest('tr');
                if (!row) return;
                const qty = row.querySelector('.inv-qty');
                if (!qty) return;

                // Initialzustand (auch beim Edit korrekt setzen)
                qty.disabled = !cb.checked;

                cb.addEventListener('change', function(e){
                    qty.disabled = !e.target.checked;
                    if (!e.target.checked) qty.value = '';
                });
            }

            function init() {
                document.querySelectorAll('.inv-toggle').forEach(wireRow);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        })();
    </script>
@endpush
