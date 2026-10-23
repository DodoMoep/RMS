<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Verschiebt Dateien mit Personenbezug von der oeffentlichen Disk (public) auf die
 * private Disk (local). Die Pfade in der Datenbank bleiben unveraendert, nur der
 * Speicherort wechselt. Der Befehl ist wiederholbar.
 */
class MakeFilesPrivate extends Command
{
    /** Verzeichnisse, die nicht mehr oeffentlich erreichbar sein duerfen. */
    private const DIRECTORIES = ['delivery-notes', 'protocols'];

    protected $signature = 'files:make-private {--dry-run : Nur anzeigen, was verschoben wuerde}';

    protected $description = 'Verschiebt Lieferscheine und Protokolldateien von der oeffentlichen auf die private Disk';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $dryRun = (bool) $this->option('dry-run');

        $moved = 0;
        $skipped = 0;
        $conflicts = 0;

        foreach (self::DIRECTORIES as $directory) {
            foreach ($public->allFiles($directory) as $path) {
                if ($private->exists($path)) {
                    if ($private->size($path) !== $public->size($path)) {
                        $this->warn("Konflikt, Datei existiert privat mit anderer Groesse, wird nicht angefasst: {$path}");
                        $conflicts++;

                        continue;
                    }

                    // Bereits kopiert, nur noch die oeffentliche Kopie entfernen
                    if (! $dryRun) {
                        $public->delete($path);
                    }
                    $skipped++;

                    continue;
                }

                $this->line(($dryRun ? '[Probelauf] ' : '').$path);

                if ($dryRun) {
                    $moved++;

                    continue;
                }

                $private->writeStream($path, $public->readStream($path));

                // Erst loeschen, wenn die Kopie vollstaendig angekommen ist
                if ($private->size($path) !== $public->size($path)) {
                    $private->delete($path);
                    $this->error("Kopie unvollstaendig, Datei bleibt oeffentlich: {$path}");
                    $conflicts++;

                    continue;
                }

                $public->delete($path);
                $moved++;
            }

            if (! $dryRun) {
                $this->removeEmptyDirectories($directory);
            }
        }

        $this->info("Verschoben: {$moved}, bereits privat (oeffentliche Kopie entfernt): {$skipped}, Konflikte: {$conflicts}");

        return $conflicts > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function removeEmptyDirectories(string $directory): void
    {
        $public = Storage::disk('public');

        foreach ($public->allDirectories($directory) as $sub) {
            // Verzeichnisse ohne jede Datei (auch verschachtelt) entfernen
            if ($public->allFiles($sub) === []) {
                $public->deleteDirectory($sub);
            }
        }
    }
}
