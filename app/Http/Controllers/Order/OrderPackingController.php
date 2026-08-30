<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use Illuminate\Http\Request;

class OrderPackingController extends Controller
{
    public function index()
    {
        $orders = Order::forPacker()
            ->withCount([
                'items',
                'items as packed_items_count' => fn($q) => $q->where('is_packed', true),
            ])
            ->with(['creator', 'customer'])
            ->orderByRaw('delivery_date IS NULL, delivery_date ASC')
            ->orderByRaw('CASE WHEN items_count > 0 THEN packed_items_count / items_count ELSE 0 END ASC')
            ->paginate(15);

        return view('order.packing.index', ['orders' => $orders]);
    }

    public function show(Order $order)
    {
        if ($order->status->value !== 'in_progress') {
            return redirect()->route('packing.index')
                ->withErrors(['Diese Bestellung kann nicht verpackt werden.']);
        }

        $order->load(['items.article', 'creator']);

        return view('order.packing.show', compact('order'));
    }

    public function packItem(Request $request, OrderItem $orderItem)
    {
        $validated = $request->validate([
            'quantity' => 'nullable|integer|min:1',
        ]);

        try {
            $quantity = $validated['quantity'] ?? null;
            $orderItem->markAsPacked($quantity);

            return back()->with('ok', 'Artikel verpackt.');
        } catch (\Exception $e) {
            return back()->withErrors([$e->getMessage()]);
        }
    }

    public function unpackItem(OrderItem $orderItem)
    {
        try {
            $orderItem->unpack();
            return back()->with('ok', 'Artikel entpackt.');
        } catch (\Exception $e) {
            return back()->withErrors([$e->getMessage()]);
        }
    }

    public function completeOrder(Order $order)
    {
        try {
            $order->lockOrder();
            
            return redirect()->route('orders.show', $order)
                ->with('ok', 'Bestellung abgeschlossen und gesperrt.');
        } catch (\Exception $e) {
            return back()->withErrors([$e->getMessage()]);
        }
    }
}


