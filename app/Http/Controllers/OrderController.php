<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Article;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['creator', 'packer', 'items']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('order_number', 'like', "%{$request->search}%")
                  ->orWhere('customer_name', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->latest()->paginate(15);

        return view('orders.index', compact('orders'));
    }

    public function create()
    {
        $articles = Article::active()->orderBy('name')->get();
        $customers = Customer::active()->orderBy('name')->get();
        return view('orders.create', compact('articles', 'customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_type' => 'required|in:existing,individual',
            'customer_id' => 'required_if:customer_type,existing|nullable|exists:customers,id',
            'customer_name' => 'required_if:customer_type,individual|nullable|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string|max:50',
            'customer_address' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.article_id' => 'required|exists:articles,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
        ]);

        $orderData = [
            'status' => OrderStatus::NEW,
            'created_by' => auth()->id(),
            'notes' => $validated['notes'] ?? null,
        ];

        if ($validated['customer_type'] === 'existing') {
            $customer = Customer::find($validated['customer_id']);
            $orderData['customer_id'] = $customer->id;
            $orderData['customer_name'] = $customer->name;
            $orderData['customer_email'] = $customer->email;
            $orderData['customer_phone'] = $customer->phone;
            $orderData['customer_address'] = $customer->address;
        } else {
            $orderData['customer_id'] = null;
            $orderData['customer_name'] = $validated['customer_name'];
            $orderData['customer_email'] = $validated['customer_email'] ?? null;
            $orderData['customer_phone'] = $validated['customer_phone'] ?? null;
            $orderData['customer_address'] = $validated['customer_address'] ?? null;
        }

        $order = Order::create($orderData);

        foreach ($validated['items'] as $item) {
            $article = Article::find($item['article_id']);
            
            $order->items()->create([
                'article_id' => $article->id,
                'article_name' => $article->name,
                'article_sku' => $article->sku,
                'article_price' => $article->price,
                'quantity_ordered' => $item['quantity'],
                'notes' => $item['notes'] ?? null,
            ]);
        }

        return redirect()->route('orders.show', $order)->with('ok', 'Bestellung erfolgreich erstellt.');
    }

    public function show(Order $order)
    {
        $order->load(['creator', 'packer', 'items.article', 'history.user', 'customer']);
        return view('orders.show', compact('order'));
    }

    public function edit(Order $order)
    {
        if (!$order->canBeModified()) {
            return back()->withErrors(['Diese Bestellung kann nicht mehr bearbeitet werden.']);
        }

        $articles = Article::active()->orderBy('name')->get();
        $customers = Customer::active()->orderBy('name')->get();
        return view('orders.edit', compact('order', 'articles', 'customers'));
    }

    public function update(Request $request, Order $order)
    {
        // Refresh order to get latest data
        $order->refresh();
        
        if (!$order->canBeModified()) {
            return back()->withErrors(['Diese Bestellung kann nicht mehr bearbeitet werden. Der Status wurde möglicherweise von einem anderen Benutzer geändert.']);
        }

        $validated = $request->validate([
            'customer_type' => 'required|in:existing,individual',
            'customer_id' => 'required_if:customer_type,existing|nullable|exists:customers,id',
            'customer_name' => 'required_if:customer_type,individual|nullable|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string|max:50',
            'customer_address' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:order_items,id',
            'items.*.article_id' => 'required|exists:articles,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
            'expected_status' => 'nullable|string',
        ]);
        
        // Check if status changed since form was opened (optimistic locking)
        if (isset($validated['expected_status']) && $order->status->value !== $validated['expected_status']) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Die Bestellung wurde von einem anderen Benutzer geändert. Bitte überprüfen Sie die aktuelle Version.');
        }

        $updateData = ['notes' => $validated['notes'] ?? null];

        if ($validated['customer_type'] === 'existing') {
            $customer = Customer::find($validated['customer_id']);
            $updateData['customer_id'] = $customer->id;
            $updateData['customer_name'] = $customer->name;
            $updateData['customer_email'] = $customer->email;
            $updateData['customer_phone'] = $customer->phone;
            $updateData['customer_address'] = $customer->address;
        } else {
            $updateData['customer_id'] = null;
            $updateData['customer_name'] = $validated['customer_name'];
            $updateData['customer_email'] = $validated['customer_email'] ?? null;
            $updateData['customer_phone'] = $validated['customer_phone'] ?? null;
            $updateData['customer_address'] = $validated['customer_address'] ?? null;
        }

        $order->update($updateData);

        // Update items
        $existingItemIds = [];
        foreach ($validated['items'] as $itemData) {
            if (!empty($itemData['id'])) {
                $orderItem = $order->items()->find($itemData['id']);
                if ($orderItem) {
                    // Only update if item can be modified (not packed or partially packed)
                    if ($orderItem->canBeModified()) {
                        $orderItem->update([
                            'quantity_ordered' => $itemData['quantity'],
                            'notes' => $itemData['notes'] ?? null,
                        ]);
                    }
                    $existingItemIds[] = $orderItem->id;
                }
            } else {
                $article = Article::find($itemData['article_id']);
                $newItem = $order->items()->create([
                    'article_id' => $article->id,
                    'article_name' => $article->name,
                    'article_sku' => $article->sku,
                    'article_price' => $article->price,
                    'quantity_ordered' => $itemData['quantity'],
                    'notes' => $itemData['notes'] ?? null,
                ]);
                $existingItemIds[] = $newItem->id;
            }
        }

        // Delete removed items (only items with no packing)
        $order->items()->whereNotIn('id', $existingItemIds)
            ->where('quantity_packed', 0)
            ->where('is_packed', false)
            ->delete();

        return redirect()->route('orders.show', $order)->with('ok', 'Bestellung aktualisiert.');
    }

    public function destroy(Order $order)
    {
        if (!$order->canBeModified()) {
            return back()->withErrors(['Diese Bestellung kann nicht gelöscht werden.']);
        }

        $order->delete();
        return redirect()->route('orders.index')->with('ok', 'Bestellung gelöscht.');
    }

    public function updateStatus(Request $request, Order $order)
    {
        // Prevent changing status of delivered orders
        if ($order->status === OrderStatus::DELIVERED) {
            return back()->with('error', 'Abgeschlossene Bestellungen können nicht mehr geändert werden.');
        }

        $validated = $request->validate([
            'status' => 'required|in:new,in_progress,packed,in_delivery,delivered',
        ]);

        $newStatus = OrderStatus::from($validated['status']);

        // Define status order for validation
        $statusOrder = [
            OrderStatus::NEW->value => 1,
            OrderStatus::IN_PROGRESS->value => 2,
            OrderStatus::PACKED->value => 3,
            OrderStatus::IN_DELIVERY->value => 4,
            OrderStatus::DELIVERED->value => 5,
        ];

        // Prevent changing status backwards from packed (delivery document already generated)
        if ($order->status === OrderStatus::PACKED && $statusOrder[$newStatus->value] < $statusOrder[OrderStatus::PACKED->value]) {
            return back()->with('error', 'Der Status kann nicht zurückgesetzt werden, da bereits ein Lieferschein generiert wurde.');
        }

        // Prevent changing status backwards from in_delivery
        if ($order->status === OrderStatus::IN_DELIVERY && $statusOrder[$newStatus->value] < $statusOrder[OrderStatus::IN_DELIVERY->value]) {
            return back()->with('error', 'Der Status kann nicht zurückgesetzt werden.');
        }

        // Prevent setting status to packed if not all items are fully packed
        if ($newStatus === OrderStatus::PACKED && !$order->isFullyPacked()) {
            return back()->with('error', 'Alle Artikel müssen vollständig verpackt sein, bevor der Status auf "Verpackt" gesetzt werden kann.');
        }

        if ($newStatus === OrderStatus::DELIVERED) {
            $order->update([
                'status' => $newStatus,
                'delivered_at' => now(),
            ]);
        } else {
            $order->update(['status' => $newStatus]);
        }

        return back()->with('ok', 'Status aktualisiert.');
    }

    public function history(Order $order)
    {
        $order->load(['history.user']);
        return view('orders.history', compact('order'));
    }
}
