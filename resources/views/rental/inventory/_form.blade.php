@csrf
<div class="vstack gap-3">
    <div>
        <x-input-label for="name" :value="__('inventory.designation')"/>
        <x-text-input id="name" name="name" :value="old('name', $item->name ?? '')" required autofocus />
        <x-input-error :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="sku" :value="__('inventory.sku')"/>
        <x-text-input id="sku" name="sku" :value="old('sku', $item->sku ?? '')" />
        <x-input-error :messages="$errors->get('sku')" />
    </div>

    <div>
        <x-input-label for="description" :value="__('inventory.description')"/>
        <textarea id="description" name="description" class="form-control" rows="4">{{ old('description', $item->description ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('description')" />
    </div>
</div>

<div class="mt-3 d-flex gap-2">
    <x-primary-button>{{ __('common.common.save') }}</x-primary-button>
    <x-secondary-button onclick="history.back();return false;">{{ __('common.common.cancel') }}</x-secondary-button>
</div>
