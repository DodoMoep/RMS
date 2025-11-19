@csrf
<div class="vstack gap-3">
    <h5 class="border-bottom pb-2">{{ __('orders.customer_information') }}</h5>
    
    <!-- Customer Type Selection -->
    <div class="row">
        <div class="col-12">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="customer_type" id="customer_type_existing" 
                       value="existing" {{ old('customer_type', isset($order) && $order->customer_id ? 'existing' : 'individual') === 'existing' ? 'checked' : '' }}
                       onchange="toggleCustomerFields()">
                <label class="form-check-label" for="customer_type_existing">
                    {{ __('orders.existing_customer') }}
                </label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="customer_type" id="customer_type_individual" 
                       value="individual" {{ old('customer_type', isset($order) && $order->customer_id ? 'existing' : 'individual') === 'individual' ? 'checked' : '' }}
                       onchange="toggleCustomerFields()">
                <label class="form-check-label" for="customer_type_individual">
                    {{ __('orders.individual_customer') }}
                </label>
            </div>
        </div>
    </div>

    <!-- Existing Customer Selection -->
    <div id="existing_customer_section" style="display: none;">
        <div class="row">
            <div class="col-md-12">
                <x-input-label for="customer_id" value="{{ __('orders.select_customer') }} *"/>
                <select id="customer_id" name="customer_id" class="form-select" onchange="fillCustomerData()">
                    <option value="">-- {{ __('orders.select_customer') }} --</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" 
                                data-name="{{ $customer->name }}"
                                data-email="{{ $customer->email }}"
                                data-phone="{{ $customer->phone }}"
                                data-address="{{ $customer->address }}"
                                {{ old('customer_id', $order->customer_id ?? '') == $customer->id ? 'selected' : '' }}>
                            {{ $customer->customer_number }} - {{ $customer->name }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('customer_id')" />
            </div>
        </div>
    </div>

    <!-- Individual Customer Fields -->
    <div id="individual_customer_section">
        <div class="row">
            <div class="col-md-6">
                <x-input-label for="customer_name" value="{{ __('orders.customer_name') }} *"/>
                <x-text-input id="customer_name" name="customer_name" :value="old('customer_name', $order->customer_name ?? '')"/>
                <x-input-error :messages="$errors->get('customer_name')" />
            </div>
            <div class="col-md-6">
                <x-input-label for="customer_email" value="{{ __('orders.customer_email') }}"/>
                <x-text-input id="customer_email" name="customer_email" type="email" :value="old('customer_email', $order->customer_email ?? '')"/>
                <x-input-error :messages="$errors->get('customer_email')" />
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <x-input-label for="customer_phone" value="{{ __('orders.customer_phone') }}"/>
                <x-text-input id="customer_phone" name="customer_phone" :value="old('customer_phone', $order->customer_phone ?? '')"/>
                <x-input-error :messages="$errors->get('customer_phone')" />
            </div>
            <div class="col-md-6">
                <x-input-label for="customer_address" value="{{ __('orders.customer_address') }}"/>
                <textarea id="customer_address" name="customer_address" class="form-control" rows="2">{{ old('customer_address', $order->customer_address ?? '') }}</textarea>
                <x-input-error :messages="$errors->get('customer_address')" />
            </div>
        </div>
    </div>

    <div>
        <x-input-label for="notes" value="{{ __('orders.notes') }}"/>
        <textarea id="notes" name="notes" class="form-control" rows="2">{{ old('notes', $order->notes ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('notes')" />
    </div>

    <h5 class="border-bottom pb-2 mt-3">{{ __('orders.items') }}</h5>
    <div id="items-container">
        @if(isset($order) && $order->items->count() > 0)
            @foreach($order->items as $index => $item)
                <div class="item-row row mb-2 {{ !$item->canBeModified() ? 'opacity-50' : '' }}">
                    <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                    <div class="col-md-5">
                        <select name="items[{{ $index }}][article_id]" class="form-select form-select-sm" required {{ !$item->canBeModified() ? 'disabled' : '' }}>
                            <option value="">-- {{ __('orders.select_article') }} --</option>
                            @foreach($articles as $art)
                                <option value="{{ $art->id }}" {{ $item->article_id == $art->id ? 'selected' : '' }}>{{ $art->name }} @if($art->sku)({{ $art->sku }})@endif</option>
                            @endforeach
                        </select>
                        @if(!$item->canBeModified())
                            <input type="hidden" name="items[{{ $index }}][article_id]" value="{{ $item->article_id }}">
                            @if($item->is_packed)
                                <small class="text-success">✓ {{ __('orders.fully_packed') }}</small>
                            @elseif($item->isPartiallyPacked())
                                <small class="text-warning">⚠ {{ __('orders.partially_packed') }} ({{ $item->quantity_packed }}/{{ $item->quantity_ordered }})</small>
                            @endif
                        @endif
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="items[{{ $index }}][quantity]" value="{{ $item->quantity_ordered }}" min="1" class="form-control form-control-sm" required {{ !$item->canBeModified() ? 'readonly' : '' }}>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="items[{{ $index }}][notes]" value="{{ $item->notes }}" class="form-control form-control-sm" placeholder="{{ __('orders.notes') }}" {{ !$item->canBeModified() ? 'readonly' : '' }}>
                    </div>
                    <div class="col-md-1">
                        @if($item->canBeDeleted())
                            <button type="button" onclick="removeItem(this)" class="btn btn-sm btn-danger">×</button>
                        @else
                            <span class="text-muted" title="{{ __('orders.messages.cannot_delete_packed_item') }}">🔒</span>
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            <div class="item-row row mb-2">
                <div class="col-md-5">
                    <select name="items[0][article_id]" class="form-select form-select-sm" required>
                        <option value="">-- {{ __('orders.select_article') }} --</option>
                        @foreach($articles as $art)
                            <option value="{{ $art->id }}">{{ $art->name }} @if($art->sku)({{ $art->sku }})@endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" name="items[0][quantity]" value="1" min="1" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-4">
                    <input type="text" name="items[0][notes]" class="form-control form-control-sm" placeholder="{{ __('orders.notes') }}">
                </div>
                <div class="col-md-1">
                    <button type="button" onclick="removeItem(this)" class="btn btn-sm btn-danger">×</button>
                </div>
            </div>
        @endif
    </div>
    <div>
        <button type="button" onclick="addItem()" class="btn btn-success btn-sm">+ {{ __('orders.add_item') }}</button>
    </div>
</div>

<div class="mt-3 d-flex gap-2">
    <x-primary-button>{{ __('common.common.save') }}</x-primary-button>
    <x-secondary-button onclick="history.back();return false;">{{ __('common.common.cancel') }}</x-secondary-button>
</div>

<script>
let itemIndex = {{ isset($order) ? $order->items->count() : 1 }};
const articles = @json($articles);

// Initialize customer type visibility on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleCustomerFields();
});

function toggleCustomerFields() {
    const existingSelected = document.getElementById('customer_type_existing').checked;
    const existingSection = document.getElementById('existing_customer_section');
    const individualSection = document.getElementById('individual_customer_section');
    
    if (existingSelected) {
        existingSection.style.display = 'block';
        individualSection.style.display = 'none';
        document.getElementById('customer_id').required = true;
        document.getElementById('customer_name').required = false;
    } else {
        existingSection.style.display = 'none';
        individualSection.style.display = 'block';
        document.getElementById('customer_id').required = false;
        document.getElementById('customer_name').required = true;
    }
}

function fillCustomerData() {
    const select = document.getElementById('customer_id');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value) {
        // Show customer info as read-only
        document.getElementById('customer_name').value = selectedOption.dataset.name || '';
        document.getElementById('customer_email').value = selectedOption.dataset.email || '';
        document.getElementById('customer_phone').value = selectedOption.dataset.phone || '';
        document.getElementById('customer_address').value = selectedOption.dataset.address || '';
    }
}

function addItem() {
    const container = document.getElementById('items-container');
    const row = document.createElement('div');
    row.className = 'item-row row mb-2';
    
    let options = '<option value="">-- {{ __('orders.select_article') }} --</option>';
    articles.forEach(art => {
        options += `<option value="${art.id}">${art.name}${art.sku ? ' (' + art.sku + ')' : ''}</option>`;
    });
    
    row.innerHTML = `
        <div class="col-md-5">
            <select name="items[${itemIndex}][article_id]" class="form-select form-select-sm" required>${options}</select>
        </div>
        <div class="col-md-2">
            <input type="number" name="items[${itemIndex}][quantity]" value="1" min="1" class="form-control form-control-sm" required>
        </div>
        <div class="col-md-4">
            <input type="text" name="items[${itemIndex}][notes]" class="form-control form-control-sm" placeholder="{{ __('orders.notes') }}">
        </div>
        <div class="col-md-1">
            <button type="button" onclick="removeItem(this)" class="btn btn-sm btn-danger">×</button>
        </div>
    `;
    container.appendChild(row);
    itemIndex++;
}

function removeItem(btn) {
    const container = document.getElementById('items-container');
    const unpackedRows = container.querySelectorAll('.item-row:not(.opacity-50)');
    if (unpackedRows.length > 1) {
        btn.closest('.item-row').remove();
    } else {
        alert('{{ __('common.messages.min_one_item') }}');
    }
}
</script>
