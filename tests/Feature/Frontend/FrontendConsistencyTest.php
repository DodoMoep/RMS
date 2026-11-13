<?php

/**
 * Statische Pruefungen fuer das Frontend. Echtes Browserverhalten (Alpine, Schliessen
 * von Meldungen) ist nur im Browser pruefbar; diese Tests verhindern, dass die
 * Voraussetzungen wieder verloren gehen.
 */
function bladeViews(): array
{
    return collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views'))))
        ->filter(fn ($file) => $file->isFile() && str_ends_with($file->getFilename(), '.blade.php'))
        ->map(fn ($file) => $file->getPathname())
        ->values()
        ->all();
}

test('no view uses bootstrap data attributes that CoreUI does not evaluate', function () {
    $offenders = collect(bladeViews())
        ->filter(fn ($path) => str_contains(file_get_contents($path), 'data-bs-'))
        ->map(fn ($path) => str_replace(base_path().DIRECTORY_SEPARATOR, '', $path))
        ->values()
        ->all();

    expect($offenders)->toBe([]);
});

test('alpine is started in the main script when views use alpine directives', function () {
    $usesAlpine = collect(bladeViews())->contains(
        fn ($path) => (bool) preg_match('/\sx-data=|\sx-show=/', file_get_contents($path))
    );

    expect($usesAlpine)->toBeTrue('Dieser Test setzt mindestens eine View mit Alpine voraus.');

    $script = file_get_contents(resource_path('js/app.js'));

    expect($script)->toContain("from 'alpinejs'")
        ->and($script)->toContain('Alpine.start()');
    expect(json_decode(file_get_contents(base_path('package.json')), true)['dependencies'])->toHaveKey('alpinejs');
});

test('elements that alpine shows or hides stay hidden until alpine has started', function () {
    $form = file_get_contents(resource_path('views/contacts/_form.blade.php'));

    preg_match_all('/<[^>]*\sx-show=[^>]*>/', $form, $tags);

    expect($tags[0])->not->toBeEmpty();
    foreach ($tags[0] as $tag) {
        expect($tag)->toContain('x-cloak');
    }
    expect(file_get_contents(resource_path('scss/app.scss')))->toContain('[x-cloak]');
});

test('the success message after saving a contact has a close button CoreUI understands', function () {
    $user = userWithPermissions(['contacts.view', 'contacts.create']);

    $this->actingAs($user)
        ->followingRedirects()
        ->post(route('contacts.store'), ['type' => 'customer', 'name' => 'Meldungskunde'])
        ->assertOk()
        ->assertSee('data-coreui-dismiss="alert"', false)
        ->assertDontSee('data-bs-dismiss', false);
});

test('the contact form contains the alpine state for the type selection', function () {
    $user = userWithPermissions(['contacts.create']);

    $this->actingAs($user)
        ->get(route('contacts.create'))
        ->assertOk()
        ->assertSee('x-data=', false)
        ->assertSee('x-model="contactType"', false);
});

test('no view loads scripts from foreign hosts', function () {
    $offenders = collect(bladeViews())
        ->filter(fn ($path) => preg_match('/<script[^>]+src=["\']https?:\/\//i', file_get_contents($path)))
        ->map(fn ($path) => str_replace(base_path().DIRECTORY_SEPARATOR, '', $path))
        ->values()
        ->all();

    expect($offenders)->toBe([]);
});

test('the signature pad is bundled with vite instead of loaded from a cdn', function () {
    $app = file_get_contents(resource_path('js/app.js'));
    $signature = file_get_contents(resource_path('js/signature.js'));
    $dependencies = json_decode(file_get_contents(base_path('package.json')), true)['dependencies'];

    expect($app)->toContain("import './signature.js'")
        ->and($signature)->toContain("from 'signature_pad'")
        ->and($dependencies)->toHaveKey('signature_pad');
});

test('the protocol form uses data attributes and has no inline script', function () {
    $form = file_get_contents(resource_path('views/rental/protocol/form.blade.php'));

    expect($form)->toContain('data-signature-form')
        ->toContain('data-please-sign')
        ->not->toContain('<script')
        ->not->toContain('onsubmit=');
});
