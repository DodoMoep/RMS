<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\Request;

class DeliveryNoteController extends Controller
{
    public function generate(Order $order)
    {
        if ($order->status !== OrderStatus::PACKED) {
            return back()->withErrors(['Lieferschein kann nur für verpackte Bestellungen erstellt werden.']);
        }

        try {
            $path = $order->generateDeliveryNote();
            return back()->with('ok', 'Lieferschein wurde erstellt.');
        } catch (\Exception $e) {
            return back()->withErrors([$e->getMessage()]);
        }
    }

    public function print(Order $order)
    {
        if (!$order->delivery_note_path) {
            $order->generateDeliveryNote();
        }

        $order->markAsInDelivery();

        return response()->download(
            storage_path('app/public/' . $order->delivery_note_path)
        );
    }

    public function download(Order $order)
    {
        if (!$order->delivery_note_path) {
            return back()->withErrors(['Kein Lieferschein vorhanden.']);
        }

        return response()->download(
            storage_path('app/public/' . $order->delivery_note_path)
        );
    }
}


