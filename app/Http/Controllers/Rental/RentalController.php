<?php

namespace App\Http\Controllers\Rental;

use App\Http\Controllers\Controller;
use App\Models\Rental\{Rental, Hall, Protocol};
use App\Models\Contact;
use Illuminate\Http\Request;

class RentalController extends Controller
{
    public function index()
    {
        $rentals = Rental::with(['contact','hall','handover','returnProtocol'])
            ->latest()->paginate(15);
        return view('rental.rentals.index', compact('rentals'));
    }

    public function create()
    {
        return view('rental.rentals.create', [
            'contacts' => Contact::tenants()->orderBy('name')->get(),
            'halls'    => Hall::orderBy('name')->get(),
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'contact_id'=>'required|uuid|exists:contacts,id',
            'hall_id'=>'required|uuid|exists:halls,id',
            'start'=>'required|date',
            'end'=>'required|date|after:start',
            'price'=>'nullable|numeric|min:0',
            'deposit'=>'nullable|numeric|min:0',
            'status'=>'required|in:scheduled,active,closed,cancelled',
        ]);

        // Check for overlapping rentals
        $overlap = Rental::where('hall_id', $data['hall_id'])
            ->where('status', '!=', 'cancelled')
            ->where(function($q) use ($data) {
                $q->whereBetween('start', [$data['start'], $data['end']])
                  ->orWhereBetween('end', [$data['start'], $data['end']])
                  ->orWhere(function($q2) use ($data) {
                      $q2->where('start', '<=', $data['start'])
                         ->where('end', '>=', $data['end']);
                  });
            })->exists();

        if ($overlap) {
            return back()->withInput()->withErrors(['hall_id' => 'Diese Halle ist im gewählten Zeitraum bereits vermietet.']);
        }

        Rental::create($data);
        return redirect()->route('rentals.index')->with('ok','Vermietung angelegt.');
    }

    public function edit(Rental $rental)
    {
        return view('rental.rentals.edit', [
            'rental'   => $rental,
            'contacts' => Contact::tenants()->orderBy('name')->get(),
            'halls'    => Hall::orderBy('name')->get(),
        ]);
    }

    public function update(Request $r, Rental $rental)
    {
        $data = $r->validate([
            'contact_id'=>'required|uuid|exists:contacts,id',
            'hall_id'=>'required|uuid|exists:halls,id',
            'start'=>'required|date',
            'end'=>'required|date|after:start',
            'price'=>'nullable|numeric|min:0',
            'deposit'=>'nullable|numeric|min:0',
            'status'=>'required|in:scheduled,active,closed,cancelled',
        ]);

        // Check for overlapping rentals (excluding current rental)
        $overlap = Rental::where('hall_id', $data['hall_id'])
            ->where('id', '!=', $rental->id)
            ->where('status', '!=', 'cancelled')
            ->where(function($q) use ($data) {
                $q->whereBetween('start', [$data['start'], $data['end']])
                  ->orWhereBetween('end', [$data['start'], $data['end']])
                  ->orWhere(function($q2) use ($data) {
                      $q2->where('start', '<=', $data['start'])
                         ->where('end', '>=', $data['end']);
                  });
            })->exists();

        if ($overlap) {
            return back()->withInput()->withErrors(['hall_id' => 'Diese Halle ist im gewählten Zeitraum bereits vermietet.']);
        }

        $rental->update($data);
        return redirect()->route('rentals.index')->with('ok','Vermietung aktualisiert.');
    }

    public function destroy(Rental $rental)
    {
        if ($rental->protocols()->exists()) {
            return back()->withErrors(['Vermietung kann nicht gelöscht werden, da bereits Protokolle vorhanden sind.']);
        }

        $rental->delete();
        return back()->with('ok','Vermietung gelöscht.');
    }

    // --- Protokoll-Aktionen:

    public function createOrOpenHandover(Rental $rental)
    {
        try {
            \DB::beginTransaction();

            $proto = $rental->handover;
            if (!$proto) {
                $proto = Protocol::create([
                    'rental_id'=>$rental->id, 'type'=>'handover',
                    'checklist'=>['stromzaehler'=>null,'wasserzaehler'=>null],
                ]);

                $rental->load('hall.inventory');
                foreach ($rental->hall->inventory as $inv) {
                    $proto->items()->create([
                        'inventory_item_id'=>$inv->id,
                        'label'=>$inv->name.' (Soll: '.$inv->pivot->quantity.')',
                        'state'=>'ok',
                    ]);
                }
            }

            \DB::commit();
            return redirect()->route('protocol.form', $proto);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error creating handover protocol', ['error' => $e->getMessage()]);
            return back()->withErrors(['Fehler beim Erstellen des Übergabeprotokolls.']);
        }
    }

    public function createOrOpenReturn(Rental $rental)
    {
        try {
            \DB::beginTransaction();

            $return = $rental->returnProtocol;
            if (!$return) {
                $source = $rental->handover;
                if (!$source) {
                    $source = Protocol::create([
                        'rental_id'=>$rental->id,'type'=>'handover','checklist'=>[]
                    ]);
                }

                $return = Protocol::create([
                    'rental_id'=>$rental->id,'type'=>'return','checklist'=>$source->checklist
                ]);

                $source->load('items');
                foreach ($source->items as $src) {
                    $return->items()->create([
                        'inventory_item_id'=>$src->inventory_item_id,
                        'label'=>$src->label, 'state'=>'ok'
                    ]);
                }
            }

            \DB::commit();
            return redirect()->route('protocol.form', $return);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error creating return protocol', ['error' => $e->getMessage()]);
            return back()->withErrors(['Fehler beim Erstellen des Rückgabeprotokolls.']);
        }
    }
}
