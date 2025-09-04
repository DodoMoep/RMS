<?php

namespace App\Http\Controllers;

use App\Models\{Rental, Tenant, Hall, Protocol};
use Illuminate\Http\Request;

class RentalController extends Controller
{
    public function index()
    {
        $rentals = Rental::with(['tenant','hall','handover','returnProtocol'])
            ->latest()->paginate(15);
        return view('rentals.index', compact('rentals'));
    }

    public function create()
    {
        return view('rentals.create', [
            'tenants' => Tenant::orderBy('name')->get(),
            'halls'   => Hall::orderBy('name')->get(),
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'tenant_id'=>'required|uuid|exists:tenants,id',
            'hall_id'=>'required|uuid|exists:halls,id',
            'start'=>'required|date',
            'end'=>'required|date|after:start',
            'price'=>'nullable|numeric',
            'deposit'=>'nullable|numeric',
            'status'=>'required|in:scheduled,active,closed,cancelled',
        ]);
        Rental::create($data);
        return redirect()->route('rentals.index')->with('ok','Vermietung angelegt.');
    }

    public function edit(Rental $rental)
    {
        return view('rentals.edit', [
            'rental'=>$rental,
            'tenants'=>Tenant::orderBy('name')->get(),
            'halls'=>Hall::orderBy('name')->get(),
        ]);
    }

    public function update(Request $r, Rental $rental)
    {
        $data = $r->validate([
            'tenant_id'=>'required|uuid|exists:tenants,id',
            'hall_id'=>'required|uuid|exists:halls,id',
            'start'=>'required|date',
            'end'=>'required|date|after:start',
            'price'=>'nullable|numeric',
            'deposit'=>'nullable|numeric',
            'status'=>'required|in:scheduled,active,closed,cancelled',
        ]);
        $rental->update($data);
        return redirect()->route('rentals.index')->with('ok','Vermietung aktualisiert.');
    }

    public function destroy(Rental $rental)
    {
        $rental->delete();
        return back()->with('ok','Vermietung gelöscht.');
    }

    // --- Protokoll-Aktionen:

    public function createOrOpenHandover(Rental $rental)
    {
        $proto = $rental->handover;
        if (!$proto) {
            $proto = Protocol::create([
                'rental_id'=>$rental->id, 'type'=>'handover',
                'checklist'=>['stromzaehler'=>null,'wasserzaehler'=>null],
            ]);
            // Halle-Inventar in Positionen übernehmen:
            foreach ($rental->hall->inventory as $inv) {
                $proto->items()->create([
                    'inventory_item_id'=>$inv->id,
                    'label'=>$inv->name.' (Soll: '.$inv->pivot->quantity.')',
                    'state'=>'ok',
                ]);
            }
        }
        return redirect()->route('protocol.form', $proto);
    }

    public function createOrOpenReturn(Rental $rental)
    {
        $return = $rental->returnProtocol;
        if (!$return) {
            $source = $rental->handover ?? Protocol::create([
                'rental_id'=>$rental->id,'type'=>'handover','checklist'=>[]
            ]);
            $return = Protocol::create([
                'rental_id'=>$rental->id,'type'=>'return','checklist'=>$source->checklist
            ]);
            foreach ($source->items as $src) {
                $return->items()->create([
                    'inventory_item_id'=>$src->inventory_item_id,
                    'label'=>$src->label, 'state'=>'ok'
                ]);
            }
        }
        return redirect()->route('protocol.form', $return);
    }
}
