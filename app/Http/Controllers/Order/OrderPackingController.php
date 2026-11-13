<?php

namespace App\Http\Controllers\Order;

use App\Enums\OrderStatus;
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
            ->with(['creator', 'contact'])
            ->orderByRaw('delivery_date IS NULL, delivery_date ASC')
            ->orderByRaw('CASE WHEN items_count > 0 THEN packed_items_count / items_count ELSE 0 END ASC')
            ->paginate(15);

        return view('order.packing.index', ['orders' => $orders]);
    }

    public function show(Order $order)
    {
        if ($order->status->value !== 'in_progress') {
            return redirect()->route('packing.index')
                ->withErrors([__('packing.messages.order_not_packable')]);
        }

        $order->load(['items.article', 'creator']);

        return view('order.packing.show', compact('order'));
    }

    public function packItem(Request $request, OrderItem $orderItem)
    {
        // Positionen geloeschter Auftraege (Soft Delete) sind nicht mehr erreichbar
        abort_unless($orderItem->order, 404);

        if ($orderItem->order->status !== OrderStatus::IN_PROGRESS) {
            return back()->withErrors([__('packing.messages.order_not_packable')]);
        }

        $validated = $request->validate([
            'quantity' => 'nullable|integer|min:1',
        ]);

        try {
            $quantity = $validated['quantity'] ?? null;
            $orderItem->markAsPacked($quantity);

            return back()->with('ok', __('packing.messages.item_packed'));
        } catch (\Exception $e) {
            return back()->withErrors([$e->getMessage()]);
        }
    }

    public function unpackItem(OrderItem $orderItem)
    {
        abort_unless($orderItem->order, 404);

        if ($orderItem->order->status !== OrderStatus::IN_PROGRESS) {
            return back()->withErrors([__('packing.messages.order_not_packable')]);
        }

        try {
            $orderItem->unpack();
            return back()->with('ok', __('packing.messages.item_unpacked'));
        } catch (\Exception $e) {
            return back()->withErrors([$e->getMessage()]);
        }
    }

    public function completeOrder(Order $order)
    {
        if ($order->status !== OrderStatus::IN_PROGRESS) {
            return back()->withErrors([__('packing.messages.order_not_packable')]);
        }

        try {
            $order->lockOrder();
            
            return redirect()->route('orders.show', $order)
                ->with('ok', __('packing.messages.packing_completed'));
        } catch (\Exception $e) {
            return back()->withErrors([$e->getMessage()]);
        }
    }
}


