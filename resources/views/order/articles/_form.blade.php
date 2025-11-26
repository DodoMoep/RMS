@csrf
<div class="vstack gap-3">
    <div>
        <x-input-label for="name" value="{{ __('articles.name') }} *"/>
        <x-text-input id="name" name="name" :value="old('name', $article->name ?? '')" required autofocus/>
        <x-input-error :messages="$errors->get('name')" />
    </div>
    <div>
        <x-input-label for="sku" value="{{ __('articles.sku') }}"/>
        <x-text-input id="sku" name="sku" :value="old('sku', $article->sku ?? '')"/>
        <x-input-error :messages="$errors->get('sku')" />
        <small class="text-muted">{{ __('articles.sku_hint') }}</small>
    </div>
    <div>
        <x-input-label for="description" value="{{ __('articles.description') }}"/>
        <textarea id="description" name="description" class="form-control" rows="3">{{ old('description', $article->description ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('description')" />
    </div>
    <div>
        <x-input-label for="price" value="{{ __('articles.price') }} (€)"/>
        <x-text-input id="price" name="price" type="number" step="0.01" min="0" :value="old('price', $article->price ?? '')"/>
        <x-input-error :messages="$errors->get('price')" />
    </div>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $article->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">
            {{ __('articles.is_active') }}
        </label>
    </div>
</div>
<div class="mt-3 d-flex gap-2">
    <x-primary-button>{{ __('common.common.save') }}</x-primary-button>
    <x-secondary-button onclick="history.back();return false;">{{ __('common.common.cancel') }}</x-secondary-button>
</div>
