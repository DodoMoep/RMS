<x-app-layout>
    <div class="row g-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">{{ $protocol->type==='handover'?'Übergabe':'Rücknahme' }} – {{ $protocol->rental->hall->name }}</h5>
                    <p class="text-muted mb-0">
                        Mieter: {{ $protocol->rental->tenant->name }} –
                        Zeitraum: {{ $protocol->rental->start->format('d.m.Y H:i') }}–{{ $protocol->rental->end->format('d.m.Y H:i') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    @if($protocol->pdf_path)
                        <div class="alert alert-success">
                            Dieses Protokoll wurde finalisiert. Änderungen sind nicht mehr möglich.
                        </div>
                    @else
                        <form method="post" action="{{ route('protocol.save',$protocol) }}" enctype="multipart/form-data" class="vstack gap-3">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <x-input-label for="strom" value="Stromzähler"/>
                                <x-text-input id="strom" name="checklist[stromzaehler]" :value="data_get($protocol->checklist,'stromzaehler','')" />
                            </div>
                            <div class="col-md-6">
                                <x-input-label for="wasser" value="Wasserzähler"/>
                                <x-text-input id="wasser" name="checklist[wasserzaehler]" :value="data_get($protocol->checklist,'wasserzaehler','')" />
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                <tr><th>Position</th><th>Status</th><th>Kommentar</th><th>Gebühr (€)</th></tr>
                                </thead>
                                <tbody>
                                @foreach($protocol->items as $item)
                                    <tr>
                                        <td>{{ $item->label }}</td>
                                        <td style="min-width:140px;">
                                            <select name="items[{{ $item->id }}][state]" class="form-select form-select-sm">
                                                <option value="ok" @selected($item->state==='ok')>OK</option>
                                                <option value="missing" @selected($item->state==='missing')>Fehlt</option>
                                                <option value="damaged" @selected($item->state==='damaged')>Beschädigt</option>
                                            </select>
                                        </td>
                                        <td><input name="items[{{ $item->id }}][comment]" value="{{ $item->comment }}" class="form-control form-control-sm"></td>
                                        <td style="width:140px;"><input type="number" step="0.01" name="items[{{ $item->id }}][charge]" value="{{ $item->charge }}" class="form-control form-control-sm"></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div>
                            <x-input-label for="photos" value="Fotos"/>
                            <input type="file" id="photos" name="photos[]" accept="image/*" capture="environment" multiple class="form-control">
                        </div>

                        <div>
                            <x-input-label for="notes" value="Notizen"/>
                            <textarea id="notes" name="notes" class="form-control" rows="3">{{ $protocol->notes }}</textarea>
                        </div>

                        <div>
                            <x-primary-button>Speichern</x-primary-button>
                        </div>
                    </form>
                    @endif
                </div>
            </div>
        </div>


        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="mb-3">Unterschrift</h6>
                    @if(!$protocol->pdf_path)
                    <form method="post" action="{{ route('protocol.sign',$protocol) }}" onsubmit="return beforeSubmit()">
                        @csrf
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <x-input-label for="role" value="Rolle"/>
                                <select id="role" name="role" class="form-select">
                                    <option value="tenant">Mieter:in</option>
                                    <option value="landlord">Vermieter:in</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <x-input-label for="signer" value="Name"/>
                                <x-text-input id="signer" name="signer" required />
                            </div>
                        </div>

                        <div class="mt-3 border rounded p-2">
                            <canvas id="sig" style="width:100%; height:240px; touch-action:none;"></canvas>
                        </div>
                        <div class="mt-2 d-flex gap-2">
                            <button type="button" id="clear" class="btn btn-outline-secondary btn-sm">Zurücksetzen</button>
                            <input type="hidden" name="signature_data" id="signature_data">
                            <x-primary-button>Unterschreiben &amp; Speichern</x-primary-button>
                            @if($protocol->pdf_path)
                                <a class="btn btn-outline-primary" target="_blank" href="{{ route('protocol.pdf',$protocol) }}">Finales PDF öffnen</a>
                            @endif
                        </div>
                    </form>
                    @else
                        <p class="text-muted">Unterschriften abgeschlossen – keine weitere Signatur möglich.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
        <script>
            const canvas = document.getElementById('sig');
            const pad = new SignaturePad(canvas,{minWidth:1,maxWidth:2.5});
            const resize = () => {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * ratio;
                canvas.height = 240 * ratio;
                canvas.getContext('2d').scale(ratio, ratio);
                pad.clear();
            };
            window.addEventListener('resize', resize);
            resize();

            document.getElementById('clear').onclick = () => pad.clear();

            function beforeSubmit(){
                if (pad.isEmpty()){ alert('{{ __('common.messages.please_sign') }}'); return false; }
                document.getElementById('signature_data').value = pad.toDataURL('image/png');
                return true;
            }
        </script>
    @endpush
</x-app-layout>
