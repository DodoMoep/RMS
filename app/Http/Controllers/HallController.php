<?php
namespace App\Http\Controllers;

use App\Models\Hall;
use App\Models\InventoryItem; // <-- wichtig
use Illuminate\Http\Request;

class HallController extends Controller
{
    public function index(){
        $halls = Hall::latest()->paginate(15);
        return view('halls.index', compact('halls'));
    }

    public function create(){
        $items = InventoryItem::orderBy('name')->get(); // alle Inventarartikel
        return view('halls.create', compact('items'));
    }

    public function store(Request $r){
        $data = $r->validate([
            'name'=>'required|string|min:2',
            'address'=>'nullable|string|max:255',
            'notes'=>'nullable|string',
            // validiere Mengen (optional, s. unten)
            'inventory.*.quantity' => 'nullable|integer|min:1',
        ]);

        $hall = Hall::create($data);

        // Pivot-Daten bauen: [item_id => ['quantity' => X], ...]
        $sync = [];
        foreach ((array) $r->input('inventory', []) as $itemId => $row) {
            // nur ausgewählte + mit Menge
            if (!empty($row['selected']) && !empty($row['quantity'])) {
                $sync[$itemId] = ['quantity' => (int) $row['quantity']];
            }
        }
        $hall->inventory()->sync($sync);

        return redirect()->route('halls.index')->with('ok','Halle angelegt.');
    }

    public function edit(Hall $hall){
        $hall->load('inventory'); // vorhandene Zuordnungen
        $items = InventoryItem::orderBy('name')->get();
        return view('halls.edit', compact('hall','items'));
    }

    public function update(Request $r, Hall $hall){
        $data = $r->validate([
            'name'=>'required|string|min:2',
            'address'=>'nullable|string|max:255',
            'notes'=>'nullable|string',
            'inventory.*.quantity' => 'nullable|integer|min:1',
        ]);

        $hall->update($data);

        $sync = [];
        foreach ((array) $r->input('inventory', []) as $itemId => $row) {
            if (!empty($row['selected']) && !empty($row['quantity'])) {
                $sync[$itemId] = ['quantity' => (int) $row['quantity']];
            }
        }
        $hall->inventory()->sync($sync);

        return redirect()->route('halls.index')->with('ok','Halle aktualisiert.');
    }

    public function destroy(Hall $hall){
        $hall->inventory()->detach(); // optional sauber trennen
        $hall->delete();
        return back()->with('ok','Halle gelöscht.');
    }
}
