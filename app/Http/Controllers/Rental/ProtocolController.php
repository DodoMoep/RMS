<?php
namespace App\Http\Controllers\Rental;

use App\Http\Controllers\Controller;
use App\Models\Rental\{Protocol, ProtocolItem, Signature};
use App\Mail\ProtocolFinalizedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Storage, Mail};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class ProtocolController extends Controller
{
    public function index(){
        $protocols = Protocol::with(['rental.tenant','rental.hall','signatures'])->latest()->paginate(15);
        return view('rental.protocol.index', compact('protocols'));
    }

    public function showForm(Protocol $protocol) {
        $protocol->load('rental.tenant','rental.hall','items','signatures');
        return view('rental.protocol.form', compact('protocol'));
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
                    'photos' => 'array|max:20',
                    'photos.*' => 'image|mimes:jpeg,png,jpg|max:5120',
                ]);

                $currentPhotoCount = $protocol->photos()->count();
                $newPhotos = $r->file('photos');
                if ($currentPhotoCount + count($newPhotos) > 30) {
                    throw new \Exception('Maximale Anzahl von 30 Fotos pro Protokoll erreicht.');
                }

                foreach ($newPhotos as $file) {
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
        $r->validate([
            'role'=>'required|in:tenant,landlord',
            'signer'=>'required|string|min:2',
            'signature_data'=>'required|string'
        ]);

        try {
            \DB::beginTransaction();

            // Pessimistic lock eliminates race window — check pdf_path only on the locked row
            $protocol = \App\Models\Rental\Protocol::lockForUpdate()->find($protocol->id);
            if ($protocol->pdf_path) {
                \DB::rollBack();
                return back()->withErrors(['Dieses Protokoll ist bereits abgeschlossen.']);
            }

            $sigData = $r->input('signature_data');
            if (!str_contains($sigData, ',')) {
                throw new \Exception('Ungültige Signatur-Daten');
            }
            [$meta, $b64] = explode(',', $sigData, 2);
            $png = base64_decode($b64, true);
            if ($png === false) {
                throw new \Exception('Ungültige Signatur-Daten');
            }
            // Validate that the decoded data is a PNG image
            $imageInfo = @getimagesizefromstring($png);
            if ($imageInfo === false || $imageInfo[2] !== IMAGETYPE_PNG) {
                throw new \Exception('Signatur muss ein PNG-Bild sein.');
            }
            // Limit signature size to 2 MB to prevent disk exhaustion
            if (strlen($png) > 2 * 1024 * 1024) {
                throw new \Exception('Signaturdaten sind zu groß.');
            }
            
            $path = "protocols/{$protocol->id}/sign-".Str::uuid().".png";
            Storage::disk('public')->put($path, $png);

            $protocol->signatures()->create([
                'role'=>$r->role,'signer_name'=>$r->signer,
                'png_path'=>$path,'signed_at'=>now()
            ]);

            // Wenn beide Rollen unterschrieben haben -> PDF + Mail
            $protocol->load('signatures');
            $hasTenant  = $protocol->signatures->contains('role', 'tenant');
            $hasLandlord = $protocol->signatures->contains('role', 'landlord');

            if ($hasTenant && $hasLandlord) {
                $protocol->load(['rental.tenant','rental.hall','items','photos']);
                
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

                $tenantEmail = $protocol->rental?->tenant?->email;
                if ($tenantEmail) {
                    try {
                        Mail::to($tenantEmail)->send(new ProtocolFinalizedMail($protocol));
                    } catch (\Exception $e) {
                        \Log::error('Failed to send protocol email', [
                            'protocol_id' => $protocol->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                $message = 'Protokoll finalisiert und PDF erstellt.';
            } else {
                \DB::commit();
                $pendingRole = !$hasTenant ? 'Mieter' : 'Vermieter';
                $message = "Unterschrift gespeichert. Ausstehend: {$pendingRole}.";
            }

            return back()->with('ok', $message);
            
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

