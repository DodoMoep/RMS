<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Enums\OrderStatus;
use App\Models\Order\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        // Gedruckt wird erst ab dem Status "verpackt"
        if (!in_array($order->status, [OrderStatus::PACKED, OrderStatus::IN_DELIVERY, OrderStatus::DELIVERED], true)) {
            return back()->withErrors([__('delivery_notes.messages.not_printable')]);
        }

        if (!$order->delivery_note_path) {
            $order->generateDeliveryNote();
        }

        $this->validateStoragePath($order->delivery_note_path);

        // Der Statuswechsel erfolgt nur beim ersten Druck; ein erneuter Druck aendert nichts
        if ($order->status === OrderStatus::PACKED) {
            $order->markAsInDelivery();
        }

        return Storage::disk('local')->download($order->delivery_note_path);
    }

    public function download(Order $order)
    {
        if (!$order->delivery_note_path) {
            return back()->withErrors(['Kein Lieferschein vorhanden.']);
        }

        $this->validateStoragePath($order->delivery_note_path);

        return Storage::disk('local')->download($order->delivery_note_path);
    }

    /**
     * Prevent path traversal attacks by ensuring the resolved path stays within the storage disk root.
     */
    private function validateStoragePath(string $path): void
    {
        $diskRoot = realpath(Storage::disk('local')->path(''));
        $candidate = realpath(Storage::disk('local')->path($path));

        if ($candidate === false || $diskRoot === false || !str_starts_with($candidate, $diskRoot . DIRECTORY_SEPARATOR)) {
            abort(403, 'Ungültiger Dateipfad.');
        }

        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'Datei nicht gefunden.');
        }
    }
}


