@csrf
<div class="vstack gap-3">
    <div>
        <x-input-label for="name" value="Name *"/>
        <x-text-input id="name" name="name" :value="old('name', $article->name ?? '')" required autofocus/>
        <x-input-error :messages="$errors->get('name')" />
    </div>
    <div>
        <x-input-label for="sku" value="SKU / Artikelnummer"/>
        <x-text-input id="sku" name="sku" :value="old('sku', $article->sku ?? '')"/>
        <x-input-error :messages="$errors->get('sku')" />
        <small class="text-muted">Optional - eindeutige Artikelnummer</small>
    </div>
    <div>
        <x-input-label for="description" value="Beschreibung"/>
        <textarea id="description" name="description" class="form-control" rows="3">{{ old('description', $article->description ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('description')" />
    </div>
    <div class="row">
        <div class="col-md-6">
            <x-input-label for="price" value="Preis (€)"/>
            <x-text-input id="price" name="price" type="number" step="0.01" min="0" :value="old('price', $article->price ?? '')"/>
            <x-input-error :messages="$errors->get('price')" />
        </div>
        <div class="col-md-6">
            <x-input-label for="stock" value="Lagerbestand"/>
            <x-text-input id="stock" name="stock" type="number" min="0" :value="old('stock', $article->stock ?? 0)"/>
            <x-input-error :messages="$errors->get('stock')" />
        </div>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $article->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">
            Artikel ist aktiv
        </label>
    </div>
</div>
<div class="mt-3 d-flex gap-2">
    <x-primary-button>Speichern</x-primary-button>
    <x-secondary-button onclick="history.back();return false;">Abbrechen</x-secondary-button>
</div>
