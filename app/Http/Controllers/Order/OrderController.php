<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Enums\ContactType;
use App\Enums\OrderStatus;
use App\Models\Order\Article;
use App\Models\Contact;
use App\Models\Order\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['creator', 'packer', 'items', 'contact']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('order_number', 'like', "%{$request->search}%")
                  ->orWhereHas('contact', function($q) use ($request) {
                      $q->where('name', 'like', "%{$request->search}%");
                  });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->latest()->paginate(15);

        return view('order.orders.index', compact('orders'));
    }

    public function create()
    {
        $articles = Article::active()->orderBy('name')->get();
        $contacts = Contact::customers()->active()->orderBy('name')->get();
        return view('order.orders.create', compact('articles', 'contacts'));
    }

    public function store(Request $request)
    {
        $rules = [
            'customer_type' => 'required|in:existing,individual',
            'contact_id' => 'required_if:customer_type,existing|nullable|exists:contacts,id',
            'customer_name' => 'required_if:customer_type,individual|nullable|string|max:255',
            'contact_person_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string|max:50',
            'address_notes' => 'nullable|string',
            'delivery_date' => 'required|date|after:today',
            'delivery_type' => 'required|in:delivery,pickup',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.article_id' => 'required|exists:articles,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
        ];

        if ($request->customer_type === 'individual' && $request->delivery_type === 'delivery') {
            $rules['street'] = 'required|string|max:255';
            $rules['zip_code'] = 'required|string|max:20';
            $rules['city'] = 'required|string|max:255';
        } else {
            $rules['street'] = 'nullable|string|max:255';
            $rules['zip_code'] = 'nullable|string|max:20';
            $rules['city'] = 'nullable|string|max:255';
        }

        $validated = $request->validate($rules);

        if ($validated['customer_type'] === 'existing') {
            $contact = Contact::find($validated['contact_id']);

            if ($validated['delivery_type'] === 'delivery') {
                if (empty($contact->street) || empty($contact->zip_code) || empty($contact->city)) {
                    return back()->withErrors([
                        'contact_id' => __('orders.messages.customer_needs_address_for_delivery')
                    ])->withInput();
                }
            }
        } else {
            $contact = Contact::create([
                'type'                => ContactType::Customer,
                'name'                => $validated['customer_name'],
                'contact_person_name' => $validated['contact_person_name'] ?? null,
                'email'               => $validated['customer_email'] ?? null,
                'phone'               => $validated['customer_phone'] ?? null,
                'street'              => $validated['street'] ?? null,
                'zip_code'            => $validated['zip_code'] ?? null,
                'city'                => $validated['city'] ?? null,
                'address_notes'       => $validated['address_notes'] ?? null,
                'is_active'           => true,
            ]);
        }

        $order = Order::create([
            'contact_id'    => $contact->id,
            'delivery_date' => $validated['delivery_date'],
            'delivery_type' => $validated['delivery_type'],
            'status'        => OrderStatus::NEW,
            'created_by'    => auth()->id(),
            'notes'         => $validated['notes'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            $article = Article::find($item['article_id']);

            $order->items()->create([
                'article_id'       => $article->id,
                'article_name'     => $article->name,
                'article_sku'      => $article->sku,
                'article_price'    => $article->price,
                'quantity_ordered' => $item['quantity'],
                'notes'            => $item['notes'] ?? null,
            ]);
        }

        return redirect()->route('orders.show', $order)->with('ok', __('orders.messages.order_created'));
    }

    public function show(Order $order)
    {
        $order->load(['creator', 'packer', 'items.article', 'history.user', 'contact']);
        return view('order.orders.show', compact('order'));
    }

    public function edit(Order $order)
    {
        if (!$order->canBeModified()) {
            return back()->withErrors([__('orders.messages.cannot_edit')]);
        }

        $articles = Article::active()->orderBy('name')->get();
        return view('order.orders.edit', compact('order', 'articles'));
    }

    public function update(Request $request, Order $order)
    {
        $order->refresh();

        if (!$order->canBeModified()) {
            return back()->withErrors([__('orders.messages.cannot_edit_status_changed')]);
        }

        $validated = $request->validate([
            'delivery_date' => 'required|date|after_or_equal:today',
            'delivery_type' => 'required|in:delivery,pickup',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:order_items,id',
            'items.*.article_id' => 'required|exists:articles,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
            'expected_status' => 'nullable|string',
        ]);

        if (isset($validated['expected_status']) && $order->status->value !== $validated['expected_status']) {
            return redirect()->route('orders.show', $order)
                ->with('error', __('orders.messages.order_changed_by_other_user'));
        }

        if ($validated['delivery_type'] === 'delivery') {
            $contact = $order->contact;
            if (!$contact || empty($contact->street) || empty($contact->zip_code) || empty($contact->city)) {
                return back()->withErrors([
                    'delivery_type' => __('orders.messages.customer_needs_address_for_delivery')
                ])->withInput();
            }
        }

        $order->update([
            'delivery_date' => $validated['delivery_date'],
            'delivery_type' => $validated['delivery_type'],
            'notes'         => $validated['notes'] ?? null,
        ]);

        $existingItemIds = [];
        foreach ($validated['items'] as $itemData) {
            if (!empty($itemData['id'])) {
                $orderItem = $order->items()->find($itemData['id']);
                if ($orderItem) {
                    if ($orderItem->canBeModified()) {
                        $orderItem->update([
                            'quantity_ordered' => $itemData['quantity'],
                            'notes'            => $itemData['notes'] ?? null,
                        ]);
                    }
                    $existingItemIds[] = $orderItem->id;
                }
            } else {
                $article = Article::find($itemData['article_id']);
                $newItem = $order->items()->create([
                    'article_id'       => $article->id,
                    'article_name'     => $article->name,
                    'article_sku'      => $article->sku,
                    'article_price'    => $article->price,
                    'quantity_ordered' => $itemData['quantity'],
                    'notes'            => $itemData['notes'] ?? null,
                ]);
                $existingItemIds[] = $newItem->id;
            }
        }

        $order->items()->whereNotIn('id', $existingItemIds)
            ->where('quantity_packed', 0)
            ->where('is_packed', false)
            ->delete();

        return redirect()->route('orders.show', $order)->with('ok', __('orders.messages.order_updated'));
    }

    public function destroy(Order $order)
    {
        if (!$order->canBeModified()) {
            return back()->withErrors([__('orders.messages.cannot_delete')]);
        }

        $order->delete();
        return redirect()->route('orders.index')->with('ok', __('orders.messages.order_deleted'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        if ($order->status === OrderStatus::DELIVERED) {
            return back()->with('error', __('orders.messages.cannot_edit_delivered'));
        }

        $validStatuses = implode(',', array_column(OrderStatus::cases(), 'value'));
        $validated = $request->validate([
            'status' => 'required|in:' . $validStatuses,
        ]);

        $newStatus = OrderStatus::from($validated['status']);

        if (!$order->status->canTransitionTo($newStatus)) {
            return back()->with('error', __('orders.messages.invalid_status_transition'));
        }

        if ($newStatus === OrderStatus::PACKED && !$order->isFullyPacked()) {
            return back()->with('error', __('orders.messages.all_items_must_be_packed'));
        }

        if ($newStatus === OrderStatus::DELIVERED) {
            $order->update([
                'status'       => $newStatus,
                'delivered_at' => now(),
            ]);
        } else {
            $order->update(['status' => $newStatus]);
        }

        return back()->with('ok', __('orders.messages.status_updated'));
    }

    public function history(Order $order)
    {
        $order->load(['history.user']);
        return view('order.orders.history', compact('order'));
    }
}
