@csrf
<div class="vstack gap-3">
    <div>
        <x-input-label for="name" value="Name"/>
        <x-text-input id="name" name="name" :value="old('name', $tenant->name ?? '')" required autofocus/>
        <x-input-error :messages="$errors->get('name')" />
    </div>
    <div>
        <x-input-label for="email" value="E-Mail"/>
        <x-text-input id="email" name="email" type="email" :value="old('email', $tenant->email ?? '')"/>
        <x-input-error :messages="$errors->get('email')" />
    </div>
    <div>
        <x-input-label for="phone" value="Telefon"/>
        <x-text-input id="phone" name="phone" :value="old('phone', $tenant->phone ?? '')"/>
        <x-input-error :messages="$errors->get('phone')" />
    </div>
</div>
<div class="mt-3 d-flex gap-2">
    <x-primary-button>Speichern</x-primary-button>
    <x-secondary-button onclick="history.back();return false;">Abbrechen</x-secondary-button>
</div>
