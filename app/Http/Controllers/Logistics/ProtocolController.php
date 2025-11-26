<?php
namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\{Protocol, ProtocolItem, Signature};
use App\Mail\ProtocolFinalizedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Storage, Mail};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class ProtocolController extends Controller
{
    public function index(){
        $protocols = Protocol::with(['rental.tenant','rental.hall','signatures'])->latest()->paginate(15);
        return view('logistics.protocol.index', compact('protocols'));
    }

    public function showForm(Protocol $protocol) {
        $protocol->load('rental.tenant','rental.hall','items','signatures');
        return view('logistics.protocol.form', compact('protocol'));
    }

    public function saveForm(Request $r, Protocol $protocol) {

        if ($protocol->pdf_path) {
            return back()->withErrors(['Protokoll ist bereits finalisiert und kann nicht mehr geändert werden.']);
        }

        try {
            \DB::beginTransaction();
            
            $protocol->update([
                'notes'=>$r->input('notes'),
                'checklist'=>[
                    'stromzaehler'=>$r->input('checklist.stromzaehler'),
                    'wasserzaehler'=>$r->input('checklist.wasserzaehler'),
                ],
            ]);

            foreach ($r->input('items',[]) as $id=>$data) {
                ProtocolItem::where('id',$id)->where('protocol_id',$protocol->id)->update([
                    'state'=>$data['state'] ?? 'ok',
                    'comment'=>$data['comment'] ?? null,
                    'charge'=>$data['charge'] ?? null,
                ]);
            }

            if ($r->hasFile('photos')) {
                $r->validate([
                    'photos.*' => 'image|mimes:jpeg,png,jpg|max:5120'
                ]);
                
                foreach ($r->file('photos') as $file) {
                    $path = $file->store("protocols/{$protocol->id}/photos",'public');
                    $protocol->photos()->create(['path'=>$path,'caption'=>null]);
                }
            }

            \DB::commit();
            return back()->with('ok','Protokoll gespeichert.');
            
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Protocol save error', ['error' => $e->getMessage()]);
            return back()->withErrors(['Ein Fehler ist aufgetreten beim Speichern.']);
        }
    }

    public function sign(Request $r, Protocol $protocol) {
        if ($protocol->pdf_path) {
            return back()->withErrors(['Dieses Protokoll ist bereits abgeschlossen.']);
        }

        $r->validate([
            'role'=>'required|in:tenant,landlord',
            'signer'=>'required|string|min:2',
            'signature_data'=>'required|string'
        ]);

        try {
            \DB::beginTransaction();
            
            // Reload to check for race condition
            $protocol->refresh();
            if ($protocol->pdf_path) {
                \DB::rollBack();
                return back()->withErrors(['Dieses Protokoll wurde gerade von jemand anderem abgeschlossen.']);
            }

            [$meta,$b64] = explode(',', $r->input('signature_data'), 2);
            $png = base64_decode($b64);
            if ($png === false) {
                throw new \Exception('Ungültige Signatur-Daten');
            }
            
            $path = "protocols/{$protocol->id}/sign-".Str::uuid().".png";
            Storage::disk('public')->put($path, $png);

            $protocol->signatures()->create([
                'role'=>$r->role,'signer_name'=>$r->signer,
                'png_path'=>$path,'signed_at'=>now()
            ]);

            // Wenn beide Rollen unterschrieben haben -> PDF + Mail
            $hasTenant = $protocol->signatures()->where('role','tenant')->exists();
            $hasLandlord = $protocol->signatures()->where('role','landlord')->exists();

            if ($hasTenant && $hasLandlord) {
                $protocol->load(['rental.tenant','rental.hall','items','signatures','photos']);
                
                $signDataUris = [];
                foreach ($protocol->signatures as $sig) {
                    if (Storage::disk('public')->exists($sig->png_path)) {
                        $bin = Storage::disk('public')->get($sig->png_path);
                        $signDataUris[$sig->id] = 'data:image/png;base64,'.base64_encode($bin);
                    }
                }

                $photoDataUris = [];
                foreach ($protocol->photos as $ph) {
                    if (Storage::disk('public')->exists($ph->path)) {
                        $bin = Storage::disk('public')->get($ph->path);
                        $mime = str_ends_with(strtolower($ph->path), '.jpg') || str_ends_with(strtolower($ph->path), '.jpeg')
                            ? 'image/jpeg'
                            : 'image/png';
                        $photoDataUris[$ph->id] = 'data:'.$mime.';base64,'.base64_encode($bin);
                    }
                }

                $pdf = Pdf::loadView('pdf.protocol', [
                    'protocol'      => $protocol,
                    'signDataUris'  => $signDataUris,
                    'photoDataUris' => $photoDataUris,
                ]);
                $pdfBytes = $pdf->output();
                $pdfPath = "protocols/{$protocol->id}/final.pdf";
                Storage::disk('public')->put($pdfPath, $pdfBytes);

                if ($protocol->type === 'handover') {
                    $rental = $protocol->rental;
                    if ($rental && $rental->status === 'scheduled') {
                        $rental->update(['status' => 'active']);
                    }
                }

                if ($protocol->type === 'return') {
                    $rental = $protocol->rental;
                    if ($rental && $rental->status !== 'closed') {
                        $rental->update(['status' => 'closed']);
                    }
                }

                $protocol->update([
                    'pdf_path'=>$pdfPath,
                    'pdf_sha256'=>hash('sha256',$pdfBytes),
                    'meta'=>array_merge($protocol->meta ?? [], ['ip'=>request()->ip(),'ua'=>request()->userAgent()])
                ]);

                // Send email after commit
                \DB::commit();
                
                if ($protocol->rental->tenant->email) {
                    try {
                        Mail::to($protocol->rental->tenant->email)->send(new ProtocolFinalizedMail($protocol));
                    } catch (\Exception $e) {
                        \Log::error('Failed to send protocol email', [
                            'protocol_id' => $protocol->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            } else {
                \DB::commit();
            }

            return back()->with('ok','Unterschrift gespeichert.');
            
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Protocol signing error', ['error' => $e->getMessage()]);
            return back()->withErrors(['Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.']);
        }
    }

    public function pdf(Protocol $protocol) {
        abort_unless($protocol->pdf_path, 404);
        return response()->file(Storage::disk('public')->path($protocol->pdf_path));
    }
}

