<?php
namespace App\Mail;

use App\Models\Protocol;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProtocolFinalizedMail extends Mailable
{
    use Queueable, SerializesModels;
    public function __construct(public Protocol $protocol){}
    public function build() {
        $path = Storage::disk('public')->path($this->protocol->pdf_path);
        return $this->subject('Ihr Übergabe-/Rückgabeprotokoll')
            ->view('emails.protocol_finalized')
            ->attach($path, ['as'=>'Protokoll.pdf','mime'=>'application/pdf']);
    }
}
