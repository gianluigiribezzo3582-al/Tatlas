<?php

use App\Models\Artist;
use App\Models\RemovalRequest;
use App\Models\Studio;
use App\Models\Style;
use App\Models\Work;
use Database\Seeders\StyleSeeder;

beforeEach(function () {
    $this->seed(StyleSeeder::class);
    $this->travelTo('2026-12-01');
    config(['tatlas.public_url' => 'https://tatlas.test']);
});

function jsonLdOf(string $html): array
{
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);

    return json_decode($m[1] ?? 'null', true) ?? [];
}

it('renders a public artist page with SEO metadata and JSON-LD Person', function () {
    $studio = Studio::factory()->at(41.12, 16.87)->create(['name' => 'Studio Nero', 'city' => 'Bari']);
    $artist = Artist::factory()->create([
        'slug' => 'mara-ink', 'name' => 'Mara Ink', 'bio' => 'Fine line <b>delicata</b>.',
        'instagram_url' => 'https://www.instagram.com/mara.ink',
    ]);
    $artist->styles()->attach(Style::where('slug', 'fine-line')->value('id'));
    $artist->studios()->attach($studio, ['type' => 'resident']);
    Work::create(['artist_id' => $artist->id, 'image_path' => 'works/1.jpg', 'thumbnail_path' => 'works/1_thumb.jpg']);

    $response = $this->get('/artisti/mara-ink')->assertOk()
        ->assertSee('<link rel="canonical" href="https://tatlas.test/artisti/mara-ink">', false)
        ->assertSee('Mara Ink – tatuatore a Bari')
        ->assertSee('tatlas://artist/mara-ink')
        ->assertSee('Fine line &lt;b&gt;delicata&lt;/b&gt;.', false)
        ->assertSee('maplibre-gl@4.7.1')
        ->assertSee('OpenStreetMap contributors')
        ->assertDontSee('noindex');

    $ld = jsonLdOf($response->getContent());
    expect($ld['@type'])->toBe('Person')
        ->and($ld['knowsAbout'])->toBe(['Fine line'])
        ->and($ld['sameAs'])->toBe(['https://www.instagram.com/mara.ink'])
        ->and($ld['worksFor'][0]['@type'])->toBe('TattooParlor')
        ->and($ld['worksFor'][0]['geo']['latitude'])->toBe(41.12);
});

it('hides unverified, hidden and unknown artists', function () {
    Artist::factory()->unverified()->create(['slug' => 'new-one']);
    $hidden = Artist::factory()->create(['slug' => 'gone']);
    $hidden->forceFill(['hidden_at' => now()])->save();

    $this->get('/artisti/new-one')->assertNotFound();
    $this->get('/artisti/gone')->assertNotFound();
    $this->get('/artisti/nobody')->assertNotFound();
});

it('renders a studio page with TattooParlor JSON-LD and redirects to the canonical slug', function () {
    $studio = Studio::factory()->fromOsm(77)->at(41.1225, 16.8689)->create([
        'name' => 'Inchiostro Murattiano', 'city' => 'Bari', 'street' => 'Via Sparano', 'housenumber' => '12',
        'opening_hours' => 'Tu-Sa 10:00-19:00', 'phone' => '+39 080 000 0001',
    ]);
    $guest = Artist::factory()->create(['name' => 'Ospite']);
    $guest->studios()->attach($studio, ['type' => 'guest', 'starts_at' => '2026-12-10', 'ends_at' => '2026-12-12']);

    $path = "/studi/{$studio->id}-inchiostro-murattiano";
    $response = $this->get($path)->assertOk()
        ->assertSee('Ospite')
        ->assertSee('openstreetmap.org/node/77', false)
        ->assertSee('Segnala un errore o chiedi la rimozione');

    $ld = jsonLdOf($response->getContent());
    expect($ld['@type'])->toBe('TattooParlor')
        ->and($ld['openingHours'])->toBe('Tu-Sa 10:00-19:00')
        ->and($ld['address']['streetAddress'])->toBe('Via Sparano 12');

    $this->get("/studi/{$studio->id}")->assertRedirect($path);
    $this->get("/studi/{$studio->id}-old-name")->assertStatus(301);
    $this->get('/studi/abc')->assertNotFound();
});

it('marks demo profiles as noindex and leaves them out of the sitemap', function () {
    Artist::factory()->create(['slug' => 'demo-alba-ink']);
    Artist::factory()->create(['slug' => 'real-artist']);
    Artist::factory()->unverified()->create(['slug' => 'pending-artist']);
    $studio = Studio::factory()->create(['name' => 'Bello Studio']);

    $this->get('/artisti/demo-alba-ink')->assertSee('noindex');

    $this->get('/sitemap.xml')->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee('https://tatlas.test/artisti/real-artist')
        ->assertSee("https://tatlas.test/studi/{$studio->id}-bello-studio")
        ->assertDontSee('demo-alba-ink')
        ->assertDontSee('pending-artist');
});

it('serves robots.txt and Digital Asset Links', function () {
    $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('Sitemap: https://tatlas.test/sitemap.xml');

    $this->getJson('/.well-known/assetlinks.json')->assertOk()->assertExactJson([]);

    config(['tatlas.android_cert_sha256' => 'AA:BB, CC:DD']);
    $this->getJson('/.well-known/assetlinks.json')
        ->assertJsonPath('0.target.package_name', 'app.tatlas')
        ->assertJsonPath('0.target.sha256_cert_fingerprints', ['AA:BB', 'CC:DD']);
});

it('exposes public URLs in the API', function () {
    $studio = Studio::factory()->create(['name' => 'Bello Studio']);

    $this->getJson("/api/v1/studios/{$studio->id}")
        ->assertJsonPath('data.public_url', "https://tatlas.test/studi/{$studio->id}-bello-studio");
});

it('shows the privacy notice', function () {
    $this->get('/privacy')->assertOk()->assertSee('Informativa privacy')->assertSee('Garante');
});

it('accepts removal requests from the web form, prefilled with the subject', function () {
    $artist = Artist::factory()->create(['name' => 'Da Rimuovere']);

    $this->get("/rimozione?tipo=artist&id={$artist->id}")->assertOk()->assertSee('Da Rimuovere');

    $this->post('/rimozione', [
        'requester_name' => 'Maria', 'requester_email' => 'maria@example.com',
        'subject_type' => 'artist', 'subject_id' => $artist->id,
        'reason' => 'Questo profilo usa le mie foto senza permesso.',
    ])->assertRedirect('/rimozione');

    $this->get('/rimozione')->assertSee('Richiesta ricevuta');
    expect(RemovalRequest::first())->subject_type->toBe('artist')->subject_id->toBe($artist->id);
});

it('silently drops honeypot submissions', function () {
    $this->post('/rimozione', [
        'requester_name' => 'Bot', 'requester_email' => 'bot@example.com',
        'reason' => 'spam spam spam spam', 'website_url' => 'http://spam.example',
    ])->assertRedirect('/rimozione');

    expect(RemovalRequest::count())->toBe(0);
});

