<x-app-layout>
    <div class="row g-4">
        <div class="col-12">
            <x-flash />

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">{{ __('protocols.types.'.$protocol->type) }} – {{ $protocol->rental->hall->name }}</h5>
                    <p class="text-muted mb-0">
                        {{ __('protocols.form.tenant') }}: {{ $protocol->rental->contact->name }} –
                        {{ __('protocols.form.period') }}: {{ $protocol->rental->start->format('d.m.Y H:i') }}–{{ $protocol->rental->end->format('d.m.Y H:i') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    @if($protocol->pdf_path)
                        <div class="alert alert-success">
                            {{ __('protocols.form.finalized_notice') }}
                        </div>
                    @else
                        <form method="post" action="{{ route('protocol.save',$protocol) }}" enctype="multipart/form-data" class="vstack gap-3">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <x-input-label for="strom" :value="__('protocols.form.electricity_meter')"/>
                                <x-text-input id="strom" name="checklist[stromzaehler]" :value="data_get($protocol->checklist,'stromzaehler','')" />
                            </div>
                            <div class="col-md-6">
                                <x-input-label for="wasser" :value="__('protocols.form.water_meter')"/>
                                <x-text-input id="wasser" name="checklist[wasserzaehler]" :value="data_get($protocol->checklist,'wasserzaehler','')" />
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                <tr><th>{{ __('protocols.form.position') }}</th><th>{{ __('protocols.form.status') }}</th><th>{{ __('protocols.form.comment') }}</th><th>{{ __('protocols.form.charge') }}</th></tr>
                                </thead>
                                <tbody>
                                @foreach($protocol->items as $item)
                                    <tr>
                                        <td>{{ $item->label }}</td>
                                        <td style="min-width:140px;">
                                            <select name="items[{{ $item->id }}][state]" class="form-select form-select-sm">
                                                @foreach(['ok', 'missing', 'damaged'] as $state)
                                                    <option value="{{ $state }}" @selected($item->state===$state)>{{ __('protocols.states.'.$state) }}</option>
                                                @endforeach
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
                            <x-input-label for="photos" :value="__('protocols.form.photos')"/>
                            <input type="file" id="photos" name="photos[]" accept="image/*" capture="environment" multiple class="form-control">
                            {{-- get('photos.*') liefert ein verschachteltes Array, x-input-error braucht eine flache Liste --}}
                            <x-input-error :messages="collect($errors->get('photos'))->merge(collect($errors->get('photos.*'))->flatten())->flatten()->all()" />
                        </div>

                        <div>
                            <x-input-label for="notes" :value="__('protocols.form.notes')"/>
                            <textarea id="notes" name="notes" class="form-control" rows="3">{{ $protocol->notes }}</textarea>
                        </div>

                        <div>
                            <x-primary-button>{{ __('common.common.save') }}</x-primary-button>
                        </div>
                    </form>
                    @endif
                </div>
            </div>
        </div>


        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="mb-3">{{ __('protocols.form.signature') }}</h6>
                    @if(!$protocol->pdf_path)
                    <form method="post" action="{{ route('protocol.sign',$protocol) }}" data-signature-form data-please-sign="{{ __('common.messages.please_sign') }}">
                        @csrf
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <x-input-label for="role" :value="__('protocols.form.role')"/>
                                <select id="role" name="role" class="form-select">
                                    <option value="tenant">{{ __('protocols.roles.tenant') }}</option>
                                    <option value="landlord">{{ __('protocols.roles.landlord') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <x-input-label for="signer" :value="__('protocols.form.name')"/>
                                <x-text-input id="signer" name="signer" required />
                                <x-input-error :messages="$errors->get('signer')" />
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('signature_data')" />

                        <div class="mt-3 border rounded p-2">
                            <canvas id="sig" style="width:100%; height:240px; touch-action:none;"></canvas>
                        </div>
                        <div class="mt-2 d-flex gap-2">
                            <button type="button" id="clear" class="btn btn-outline-secondary btn-sm">{{ __('common.common.reset') }}</button>
                            <input type="hidden" name="signature_data" id="signature_data">
                            <x-primary-button>{{ __('protocols.form.sign_and_save') }}</x-primary-button>
                            @if($protocol->pdf_path)
                                <a class="btn btn-outline-primary" target="_blank" href="{{ route('protocol.pdf',$protocol) }}">{{ __('protocols.form.open_final_pdf') }}</a>
                            @endif
                        </div>
                    </form>
                    @else
                        <p class="text-muted">{{ __('protocols.form.signatures_done') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
