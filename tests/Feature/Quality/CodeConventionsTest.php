<?php

use App\Models\Contact;
use App\Models\Order\Article;
use App\Models\Order\Order;
use App\Models\Order\OrderHistory;
use App\Models\Order\OrderItem;
use App\Models\Rental\Hall;
use App\Models\Rental\InventoryItem;
use App\Models\Rental\Photo;
use App\Models\Rental\Protocol;
use App\Models\Rental\ProtocolItem;
use App\Models\Rental\Rental;
use App\Models\Rental\Signature;
use App\Models\WorkInstruction;
use App\Models\WorkInstructionCategory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Arr;

/**
 * Statische Pruefungen fuer Konventionen aus docs/07-coding-guidelines.md. Sie verhindern,
 * dass behobene technische Schulden zurueckkommen.
 */
function phpFilesIn(string $directory): array
{
    return collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)))
        ->filter(fn ($file) => $file->isFile() && str_ends_with($file->getFilename(), '.php'))
        ->map(fn ($file) => $file->getPathname())
        ->values()
        ->all();
}

function relativePath(string $path): string
{
    return str_replace([base_path().DIRECTORY_SEPARATOR, '\\'], ['', '/'], $path);
}

// ------------------------------------------------------------------ Sprachdateien

test('every language file can be loaded and de and en have identical keys', function () {
    $mismatches = [];

    foreach (glob(lang_path('de/*.php')) as $deFile) {
        $name = basename($deFile);
        $enFile = lang_path("en/{$name}");

        if (! is_file($enFile)) {
            $mismatches[] = "{$name}: fehlt in en";

            continue;
        }

        $de = array_keys(Arr::dot(require $deFile));
        $en = array_keys(Arr::dot(require $enFile));

        foreach (array_diff($de, $en) as $key) {
            $mismatches[] = "{$name}: {$key} fehlt in en";
        }
        foreach (array_diff($en, $de) as $key) {
            $mismatches[] = "{$name}: {$key} fehlt in de";
        }
    }

    foreach (glob(lang_path('en/*.php')) as $enFile) {
        if (! is_file(lang_path('de/'.basename($enFile)))) {
            $mismatches[] = basename($enFile).': fehlt in de';
        }
    }

    expect($mismatches)->toBe([]);
});

// ------------------------------------------------------- Texte in den Controllern

test('controllers do not contain hardcoded user messages', function () {
    $patterns = [
        "/->with\('(?:ok|status|error)',\s*'[^']*\s[^']*'\)/",      // ->with('ok', 'Text mit Leerzeichen')
        '/withErrors\(\[\s*(?:\'[^\']+\'\s*=>\s*)?\'[^\']*\s[^\']*\'\s*\]\)/', // withErrors(['Text']) oder ['feld' => 'Text']
        "/abort(?:_if|_unless)?\([^;]*,\s*'[^']*\s[^']*'\)/",       // abort(403, 'Text')
    ];

    $offenders = [];

    foreach (phpFilesIn(app_path('Http/Controllers')) as $file) {
        $code = file_get_contents($file);

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $code, $matches)) {
                foreach ($matches[0] as $match) {
                    $offenders[] = relativePath($file).': '.$match;
                }
            }
        }
    }

    expect($offenders)->toBe([]);
});

// ---------------------------------------------------------------- Aliase und Importe

test('application code imports facades instead of using global aliases', function () {
    $offenders = [];

    foreach (phpFilesIn(app_path()) as $file) {
        if (preg_match_all('/(?<![\w\\\\])\\\\(DB|Log|PDF|Storage|Artisan|Auth|Hash|Mail|Cache|Session)::/', file_get_contents($file), $matches)) {
            $offenders[] = relativePath($file).': \\'.implode(', \\', array_unique($matches[1]));
        }
    }

    expect($offenders)->toBe([]);
});

test('application code does not use fully qualified model classes inline', function () {
    $offenders = [];

    foreach (phpFilesIn(app_path()) as $file) {
        $code = file_get_contents($file);
        // Nur Verwendungen im Code, nicht die namespace- und use-Zeilen
        $body = preg_replace('/^(namespace|use) .*$/m', '', $code);

        if (preg_match('/\\\\App\\\\(Models|Observers|Enums)\\\\/', $body)) {
            $offenders[] = relativePath($file);
        }
    }

    expect($offenders)->toBe([]);
});

// ------------------------------------------------------------------ Toter Code

test('removed dead code does not come back', function () {
    // Dateien statt class_exists: die optimierte Klassenkarte von Composer kennt gelöschte Klassen noch
    expect(file_exists(app_path('Http/Controllers/LocaleController.php')))->toBeFalse()
        ->and(file_exists(app_path('Http/Controllers/Auth/RegisteredUserController.php')))->toBeFalse()
        ->and(file_exists(app_path('Models/Concerns/HasUuid.php')))->toBeFalse();

    foreach (['application-logo', 'danger-button', 'dropdown', 'dropdown-link', 'modal', 'responsive-nav-link'] as $component) {
        expect(file_exists(resource_path("views/components/{$component}.blade.php")))->toBeFalse();
    }
});

test('unused packages are not required', function () {
    $composer = json_decode(file_get_contents(base_path('composer.json')), true);

    expect($composer['require'])->not->toHaveKeys(['spatie/laravel-medialibrary', 'tuytoosh/active']);
});

// ----------------------------------------------------------------- Eine UUID-Variante

test('all models with uuid keys use the framework trait', function () {
    $models = [
        Contact::class,
        WorkInstruction::class,
        WorkInstructionCategory::class,
        Article::class,
        Order::class,
        OrderItem::class,
        OrderHistory::class,
        Hall::class,
        InventoryItem::class,
        Photo::class,
        Protocol::class,
        ProtocolItem::class,
        Rental::class,
        Signature::class,
    ];

    foreach ($models as $model) {
        expect(in_array(HasUuids::class, class_uses_recursive($model)))
            ->toBeTrue("{$model} verwendet nicht HasUuids");
    }
});

test('rental models create string uuid keys', function () {
    $hall = Hall::create(['name' => 'Schluesselhalle']);

    expect($hall->getIncrementing())->toBeFalse()
        ->and($hall->getKeyType())->toBe('string')
        ->and($hall->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

// -------------------------------------------------- Keine Rohtexte von Ausnahmen (S2)

test('controllers never show exception messages to users', function () {
    $offenders = [];

    foreach (phpFilesIn(app_path('Http/Controllers')) as $file) {
        if (preg_match_all('/(?:withErrors|->with)\([^;]*getMessage\(\)/', file_get_contents($file), $matches)) {
            foreach ($matches[0] as $match) {
                $offenders[] = relativePath($file).': '.$match;
            }
        }
    }

    expect($offenders)->toBe([]);
});

test('the models only throw the workflow exception for business errors', function () {
    $offenders = [];

    foreach (phpFilesIn(app_path('Models')) as $file) {
        if (preg_match('/throw new \\\\Exception\(/', file_get_contents($file))) {
            $offenders[] = relativePath($file);
        }
    }

    expect($offenders)->toBe([]);
});

// -------------------------------------------- Lokale Werkzeugeinstellungen (S4)

test('local tool settings are ignored by git', function () {
    expect(file_get_contents(base_path('.gitignore')))->toContain('/.claude/settings.local.json');
});
