@csrf
@php
    $startVal = old('start', optional($rental->start)->format('Y-m-d\TH:i'));
    $endVal   = old('end',   optional($rental->end)->format('Y-m-d\TH:i'));
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <x-input-label for="tenant_id" value="Mieter:in"/>
        <select id="tenant_id" name="tenant_id" class="form-select" required>
            <option value="">Bitte wählen…</option>
            @foreach($tenants as $t)
                <option value="{{ $t->id }}" @selected(old('tenant_id', $rental->tenant_id ?? '')==$t->id)>{{ $t->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('tenant_id')" />
    </div>

    <div class="col-md-6">
        <x-input-label for="hall_id" value="Halle"/>
        <select id="hall_id" name="hall_id" class="form-select" required>
            <option value="">Bitte wählen…</option>
            @foreach($halls as $h)
                <option value="{{ $h->id }}" @selected(old('hall_id', $rental->hall_id ?? '')==$h->id)>{{ $h->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('hall_id')" />
    </div>

    <div class="col-md-6">
        <x-input-label for="start" value="Start"/>
        <x-text-input id="start" name="start" type="datetime-local" :value="$startVal" required />
        <x-input-error :messages="$errors->get('start')" />
    </div>

    <div class="col-md-6">
        <x-input-label for="end" value="Ende"/>
        <x-text-input id="end" name="end" type="datetime-local" :value="$endVal" required />
        <x-input-error :messages="$errors->get('end')" />
    </div>

    <div class="col-md-6">
        <x-input-label for="price" value="Preis (€)"/>
        <x-text-input id="price" name="price" type="number" step="0.01" :value="old('price', $rental->price ?? '')" />
        <x-input-error :messages="$errors->get('price')" />
    </div>

    <div class="col-md-6">
        <x-input-label for="deposit" value="Kaution (€)"/>
        <x-text-input id="deposit" name="deposit" type="number" step="0.01" :value="old('deposit', $rental->deposit ?? '')" />
        <x-input-error :messages="$errors->get('deposit')" />
    </div>

    <div class="col-md-6">
        <x-input-label for="status" value="Status"/>
        <select id="status" name="status" class="form-select">
            @foreach(['scheduled'=>'geplant','active'=>'aktiv','closed'=>'geschlossen','cancelled'=>'storniert'] as $k=>$v)
                <option value="{{ $k }}" @selected(old('status', $rental->status ?? 'scheduled')==$k)>{{ $v }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('status')" />
    </div>
</div>

<div class="mt-3 d-flex gap-2">
    <x-primary-button>Speichern</x-primary-button>
    <x-secondary-button onclick="history.back();return false;">Abbrechen</x-secondary-button>
</div>
