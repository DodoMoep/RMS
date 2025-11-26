<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query()->withCount('orders');

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('customer_number', 'like', "%{$request->search}%")
                  ->orWhere('contact_person_name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%")
                  ->orWhere('street', 'like', "%{$request->search}%")
                  ->orWhere('city', 'like', "%{$request->search}%")
                  ->orWhere('zip_code', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $customers = $query->latest()->paginate(15);

        return view('customer.customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customer.customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person_name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'street' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'address_notes' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $customer = Customer::create($validated);

        return redirect()->route('customers.show', $customer)->with('ok', __('customers.messages.created'));
    }

    public function show(Customer $customer)
    {
        $customer->loadCount('orders');
        $customer->load(['orders' => function($query) {
            $query->withCount('items')->latest()->take(10);
        }]);
        
        return view('customer.customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('customer.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person_name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'street' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'address_notes' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $customer->update($validated);

        return redirect()->route('customers.show', $customer)->with('ok', __('customers.messages.updated'));
    }

    public function destroy(Customer $customer)
    {
        if ($customer->orders()->count() > 0) {
            return back()->withErrors([__('customers.messages.cannot_delete_has_orders')]);
        }

        $customer->delete();

        return redirect()->route('customers.index')->with('ok', __('customers.messages.deleted'));
    }

    public function toggleStatus(Customer $customer)
    {
        $customer->update([
            'is_active' => !$customer->is_active
        ]);

        $status = $customer->is_active ? 'activated' : 'deactivated';

        return back()->with('ok', __("customers.messages.{$status}"));
    }
}

