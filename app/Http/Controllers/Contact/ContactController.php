<?php

namespace App\Http\Controllers\Contact;

use App\Enums\ContactType;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $query = Contact::withCount(['orders', 'rentals']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('customer_number', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%")
                  ->orWhere('city', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $contacts = $query->latest()->paginate(15);

        return view('contacts.index', compact('contacts'));
    }

    public function create(Request $request)
    {
        $type = $request->query('type', 'customer');
        return view('contacts.create', compact('type'));
    }

    public function store(Request $request)
    {
        $type = $request->input('type', 'customer');

        $rules = [
            'type'    => 'required|in:tenant,customer,both',
            'name'    => 'required|string|max:255',
            'email'   => 'nullable|email',
            'phone'   => 'nullable|string|max:50',
            'notes'   => 'nullable|string',
            'is_active' => 'boolean',
        ];

        // Address and contact_person_name only required for customer/both
        if (in_array($type, ['customer', 'both'])) {
            $rules['contact_person_name'] = 'nullable|string|max:255';
            $rules['street']       = 'nullable|string|max:255';
            $rules['zip_code']     = 'nullable|string|max:20';
            $rules['city']         = 'nullable|string|max:100';
            $rules['address_notes'] = 'nullable|string';
        }

        $validated = $request->validate($rules);
        $validated['is_active'] = $request->has('is_active');

        $contact = Contact::create($validated);

        return redirect()->route('contacts.show', $contact)
            ->with('ok', __('contacts.messages.contact_created'));
    }

    public function show(Contact $contact)
    {
        if (in_array($contact->type->value, ['customer', 'both'])) {
            $contact->load(['orders' => function ($q) {
                $q->latest()->take(10);
            }]);
            $contact->loadCount('orders');
        }

        if (in_array($contact->type->value, ['tenant', 'both'])) {
            $contact->load(['rentals.hall' => function ($q) {
            }]);
            $contact->loadCount('rentals');
        }

        return view('contacts.show', compact('contact'));
    }

    public function edit(Contact $contact)
    {
        return view('contacts.edit', compact('contact'));
    }

    public function update(Request $request, Contact $contact)
    {
        $type = $request->input('type', $contact->type->value);

        $rules = [
            'type'    => 'required|in:tenant,customer,both',
            'name'    => 'required|string|max:255',
            'email'   => 'nullable|email',
            'phone'   => 'nullable|string|max:50',
            'notes'   => 'nullable|string',
            'is_active' => 'boolean',
        ];

        if (in_array($type, ['customer', 'both'])) {
            $rules['contact_person_name'] = 'nullable|string|max:255';
            $rules['street']       = 'nullable|string|max:255';
            $rules['zip_code']     = 'nullable|string|max:20';
            $rules['city']         = 'nullable|string|max:100';
            $rules['address_notes'] = 'nullable|string';
        }

        $validated = $request->validate($rules);
        $validated['is_active'] = $request->has('is_active');

        // If switching to customer/both and no customer_number, generate one
        if (in_array($type, ['customer', 'both']) && !$contact->customer_number) {
            $validated['customer_number'] = Contact::generateCustomerNumber();
        }

        $contact->update($validated);

        return redirect()->route('contacts.show', $contact)
            ->with('ok', __('contacts.messages.contact_updated'));
    }

    public function destroy(Contact $contact)
    {
        if ($contact->orders()->exists() || $contact->rentals()->exists()) {
            return back()->withErrors([__('contacts.messages.cannot_delete_has_relations')]);
        }

        $contact->delete();

        return redirect()->route('contacts.index')
            ->with('ok', __('contacts.messages.contact_deleted'));
    }

    public function toggleStatus(Contact $contact)
    {
        $contact->update(['is_active' => !$contact->is_active]);
        $status = $contact->is_active ? 'activated' : 'deactivated';

        return back()->with('ok', __("contacts.messages.{$status}"));
    }
}
