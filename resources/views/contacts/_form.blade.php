@csrf
<div class="vstack gap-3" x-data="{ contactType: '{{ old('type', $contact->type->value ?? $type ?? 'customer') }}' }">

    {{-- Type selection --}}
    <div>
        <x-input-label value="{{ __('contacts.type') }} *"/>
        <div class="d-flex gap-3 mt-1">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="type" id="type_tenant"
                       value="tenant" x-model="contactType"
                       {{ old('type', $contact->type->value ?? $type ?? 'customer') === 'tenant' ? 'checked' : '' }}>
                <label class="form-check-label" for="type_tenant">{{ __('contacts.type_tenant') }}</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="type" id="type_customer"
                       value="customer" x-model="contactType"
                       {{ old('type', $contact->type->value ?? $type ?? 'customer') === 'customer' ? 'checked' : '' }}>
                <label class="form-check-label" for="type_customer">{{ __('contacts.type_customer') }}</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="type" id="type_both"
                       value="both" x-model="contactType"
                       {{ old('type', $contact->type->value ?? $type ?? 'customer') === 'both' ? 'checked' : '' }}>
                <label class="form-check-label" for="type_both">{{ __('contacts.type_both') }}</label>
            </div>
        </div>
        <x-input-error :messages="$errors->get('type')" />
    </div>

    {{-- Name (always shown) --}}
    <div class="row">
        <div class="col-md-6">
            <x-input-label for="name" value="{{ __('contacts.name') }} *"/>
            <x-text-input id="name" name="name" :value="old('name', $contact->name ?? '')" required/>
            <x-input-error :messages="$errors->get('name')" />
        </div>
        <div class="col-md-6" x-show="contactType === 'customer' || contactType === 'both'">
            <x-input-label for="contact_person_name" value="{{ __('contacts.contact_person_name') }}"/>
            <x-text-input id="contact_person_name" name="contact_person_name"
                          :value="old('contact_person_name', $contact->contact_person_name ?? '')"/>
            <x-input-error :messages="$errors->get('contact_person_name')" />
        </div>
    </div>

    {{-- Contact details (always shown) --}}
    <div class="row">
        <div class="col-md-6">
            <x-input-label for="email" value="{{ __('contacts.email') }}"/>
            <x-text-input id="email" name="email" type="email" :value="old('email', $contact->email ?? '')"/>
            <x-input-error :messages="$errors->get('email')" />
        </div>
        <div class="col-md-6">
            <x-input-label for="phone" value="{{ __('contacts.phone') }}"/>
            <x-text-input id="phone" name="phone" :value="old('phone', $contact->phone ?? '')"/>
            <x-input-error :messages="$errors->get('phone')" />
        </div>
    </div>

    {{-- Address fields — only for customer/both --}}
    <div x-show="contactType === 'customer' || contactType === 'both'">
        <div class="row">
            <div class="col-md-6">
                <x-input-label for="street" value="{{ __('contacts.street') }}"/>
                <x-text-input id="street" name="street" :value="old('street', $contact->street ?? '')"/>
                <x-input-error :messages="$errors->get('street')" />
            </div>
            <div class="col-md-3">
                <x-input-label for="zip_code" value="{{ __('contacts.zip_code') }}"/>
                <x-text-input id="zip_code" name="zip_code" :value="old('zip_code', $contact->zip_code ?? '')"/>
                <x-input-error :messages="$errors->get('zip_code')" />
            </div>
            <div class="col-md-3">
                <x-input-label for="city" value="{{ __('contacts.city') }}"/>
                <x-text-input id="city" name="city" :value="old('city', $contact->city ?? '')"/>
                <x-input-error :messages="$errors->get('city')" />
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-md-12">
                <x-input-label for="address_notes" value="{{ __('contacts.address_notes') }}"/>
                <x-text-input id="address_notes" name="address_notes"
                              :value="old('address_notes', $contact->address_notes ?? '')"/>
                <x-input-error :messages="$errors->get('address_notes')" />
            </div>
        </div>
    </div>

    {{-- Notes --}}
    <div>
        <x-input-label for="notes" value="{{ __('contacts.notes') }}"/>
        <textarea id="notes" name="notes" class="form-control" rows="3">{{ old('notes', $contact->notes ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('notes')" />
    </div>

    {{-- Is Active --}}
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
               {{ old('is_active', $contact->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">{{ __('contacts.is_active') }}</label>
    </div>

    <div class="d-flex gap-2">
        <x-primary-button>{{ __('common.common.save') }}</x-primary-button>
        <x-secondary-button onclick="history.back();return false;">{{ __('common.common.cancel') }}</x-secondary-button>
    </div>
</div>
