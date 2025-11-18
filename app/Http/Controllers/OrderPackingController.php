<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class OrderPackingController extends Controller
{
    public function index()
    {
        $orders = Order::forPacker()
            ->with(['creator', 'items'])
            ->latest()
            ->paginate(15);

        return view('packing.index', compact('orders'));
    }

    public function show(Order $order)
    {
        if ($order->status->value !== 'in_progress') {
            return redirect()->route('packing.index')
                ->withErrors(['Diese Bestellung kann nicht verpackt werden.']);
        }

        $order->load(['items.inventoryItem', 'creator']);

        return view('packing.show', compact('order'));
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

