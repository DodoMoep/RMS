<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Article;
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
        return view('orders.create', compact('articles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string|max:50',
            'customer_address' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.article_id' => 'required|exists:articles,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
        ]);

        $order = Order::create([
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'] ?? null,
            'customer_phone' => $validated['customer_phone'] ?? null,
            'customer_address' => $validated['customer_address'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => OrderStatus::NEW,
            'created_by' => auth()->id(),
        ]);

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
        $order->load(['creator', 'packer', 'items.article', 'history.user']);
        return view('orders.show', compact('order'));
    }

    public function edit(Order $order)
    {
        if (!$order->canBeModified()) {
            return back()->withErrors(['Diese Bestellung kann nicht mehr bearbeitet werden.']);
        }

        $articles = Article::active()->orderBy('name')->get();
        return view('orders.edit', compact('order', 'articles'));
    }

    public function update(Request $request, Order $order)
    {
        if (!$order->canBeModified()) {
            return back()->withErrors(['Diese Bestellung kann nicht mehr bearbeitet werden.']);
        }

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string|max:50',
            'customer_address' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:order_items,id',
            'items.*.article_id' => 'required|exists:articles,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
        ]);

        $order->update([
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'] ?? null,
            'customer_phone' => $validated['customer_phone'] ?? null,
            'customer_address' => $validated['customer_address'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Update items
        $existingItemIds = [];
        foreach ($validated['items'] as $itemData) {
            if (!empty($itemData['id'])) {
                $orderItem = $order->items()->find($itemData['id']);
                if ($orderItem && $orderItem->canBeModified()) {
                    $orderItem->update([
                        'quantity_ordered' => $itemData['quantity'],
                        'notes' => $itemData['notes'] ?? null,
                    ]);
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

        // Delete removed items (only unpacked ones)
        $order->items()->whereNotIn('id', $existingItemIds)->where('is_packed', false)->delete();

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
        $validated = $request->validate([
            'status' => 'required|in:new,in_progress,packed,in_delivery,delivered',
        ]);

        $newStatus = OrderStatus::from($validated['status']);

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
