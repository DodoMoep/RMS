@csrf
<div class="vstack gap-3">
    <div class="row">
        <div class="col-md-6">
            <x-input-label for="name" value="{{ __('customers.name') }} *"/>
            <x-text-input id="name" name="name" :value="old('name', $customer->name ?? '')" required autofocus/>
            <x-input-error :messages="$errors->get('name')" />
        </div>
        <div class="col-md-6">
            <x-input-label for="contact_person_name" value="{{ __('customers.contact_person_name') }}"/>
            <x-text-input id="contact_person_name" name="contact_person_name" :value="old('contact_person_name', $customer->contact_person_name ?? '')"/>
            <x-input-error :messages="$errors->get('contact_person_name')" />
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <x-input-label for="email" value="{{ __('customers.email') }}"/>
            <x-text-input id="email" name="email" type="email" :value="old('email', $customer->email ?? '')"/>
            <x-input-error :messages="$errors->get('email')" />
        </div>
        <div class="col-md-6">
            <x-input-label for="phone" value="{{ __('customers.phone') }}"/>
            <x-text-input id="phone" name="phone" :value="old('phone', $customer->phone ?? '')"/>
            <x-input-error :messages="$errors->get('phone')" />
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <x-input-label for="street" value="{{ __('customers.street') }}"/>
            <x-text-input id="street" name="street" :value="old('street', $customer->street ?? '')"/>
            <x-input-error :messages="$errors->get('street')" />
        </div>
        <div class="col-md-3">
            <x-input-label for="zip_code" value="{{ __('customers.zip_code') }}"/>
            <x-text-input id="zip_code" name="zip_code" :value="old('zip_code', $customer->zip_code ?? '')"/>
            <x-input-error :messages="$errors->get('zip_code')" />
        </div>
        <div class="col-md-3">
            <x-input-label for="city" value="{{ __('customers.city') }}"/>
            <x-text-input id="city" name="city" :value="old('city', $customer->city ?? '')"/>
            <x-input-error :messages="$errors->get('city')" />
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <x-input-label for="address_notes" value="{{ __('customers.address_notes') }}"/>
            <x-text-input id="address_notes" name="address_notes" :value="old('address_notes', $customer->address_notes ?? '')"/>
            <x-input-error :messages="$errors->get('address_notes')" />
            <small class="text-muted">{{ __('customers.address_notes_help') }}</small>
        </div>
    </div>

    <div>
        <x-input-label for="notes" value="{{ __('customers.notes') }}"/>
        <textarea id="notes" name="notes" class="form-control" rows="3">{{ old('notes', $customer->notes ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('notes')" />
        <small class="text-muted">{{ __('customers.notes_help') }}</small>
    </div>

    <div class="form-check">
        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
               value="1" {{ old('is_active', $customer->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">
            {{ __('customers.is_active') }}
        </label>
        <small class="form-text text-muted d-block">
            {{ __('customers.is_active_help') }}
        </small>
    </div>
</div>

<div class="mt-3 d-flex gap-2">
    <x-primary-button>{{ __('common.common.save') }}</x-primary-button>
    <x-secondary-button onclick="history.back();return false;">{{ __('common.common.cancel') }}</x-secondary-button>
</div>
