<?php

use App\Models\Artist;
use App\Models\User;
use App\Models\Work;
use App\Services\Images\ImageProcessor;
use Database\Seeders\StyleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/** JPEG with an EXIF APP1 segment: Orientation = 6 (rotate 90° CW) plus a marker string. */
function jpegWithExifOrientation(int $width, int $height): string
{
    $img = imagecreatetruecolor($width, $height);
    ob_start();
    imagejpeg($img);
    $jpeg = ob_get_clean();

    $ifd = pack('v', 1)                                  // one entry
        .pack('vvVv', 0x0112, 3, 1, 6).pack('v', 0)      // Orientation, SHORT, 1, value 6
        .pack('V', 0);                                   // no next IFD
    $tiff = 'II*'."\0".pack('V', 8).$ifd.'SECRET-GPS-MARKER';
    $app1 = "Exif\0\0".$tiff;
    $segment = "\xFF\xE1".pack('n', strlen($app1) + 2).$app1;

    return substr($jpeg, 0, 2).$segment.substr($jpeg, 2);
}

beforeEach(function () {
    Storage::fake('public');
    $this->seed(StyleSeeder::class);
    $this->user = User::factory()->create();
    $this->artist = Artist::factory()->create(['claimed_by_user_id' => $this->user->id]);
    Sanctum::actingAs($this->user);
});

it('uploads a work, stores a re-encoded image and a thumbnail', function () {
    $response = $this->postJson('/api/v1/me/works', [
        'image' => UploadedFile::fake()->image('tattoo.png', 3000, 1500),
        'description' => 'Rosa fine line',
        'body_part' => 'forearm',
        'is_color' => false,
        'styles' => ['fine-line'],
    ])->assertCreated()
        ->assertJsonPath('data.width', 2048)
        ->assertJsonPath('data.height', 1024)
        ->assertJsonPath('data.styles.0', 'fine-line');

    $work = Work::firstOrFail();
    Storage::disk('public')->assertExists([$work->image_path, $work->thumbnail_path]);
    expect($work->image_path)->toEndWith('.jpg')
        ->and(getimagesizefromstring(Storage::disk('public')->get($work->thumbnail_path))[0])->toBe(480)
        ->and($response->json('data.thumbnail_url'))->toContain('_thumb.jpg');
});

it('applies EXIF orientation and strips metadata', function () {
    $path = tempnam(sys_get_temp_dir(), 'exif').'.jpg';
    file_put_contents($path, jpegWithExifOrientation(800, 400));

    $result = app(ImageProcessor::class)->process($path);

    expect([$result['width'], $result['height']])->toBe([400, 800])
        ->and(str_contains($result['image'], 'SECRET-GPS-MARKER'))->toBeFalse()
        ->and(str_contains($result['image'], 'Exif'))->toBeFalse();
});

it('enforces the portfolio limit', function () {
    config(['tatlas.max_works_per_artist' => 2]);
    Work::create(['artist_id' => $this->artist->id, 'image_path' => 'a.jpg']);
    Work::create(['artist_id' => $this->artist->id, 'image_path' => 'b.jpg']);

    $this->postJson('/api/v1/me/works', ['image' => UploadedFile::fake()->image('x.jpg', 600, 600)])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Portfolio limit reached (2 works).');
});

it('rejects non-images, tiny images and unknown body parts', function () {
    $this->postJson('/api/v1/me/works', [
        'image' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
    ])->assertUnprocessable()->assertJsonValidationErrors('image');

    $this->postJson('/api/v1/me/works', [
        'image' => UploadedFile::fake()->image('small.jpg', 100, 100),
        'body_part' => 'elbow-of-doom',
    ])->assertUnprocessable()->assertJsonValidationErrors(['image', 'body_part']);
});

it('updates and deletes own works only', function () {
    $this->postJson('/api/v1/me/works', ['image' => UploadedFile::fake()->image('x.jpg', 600, 600)]);
    $work = Work::firstOrFail();

    $this->patchJson("/api/v1/me/works/{$work->id}", ['description' => 'Nuova', 'styles' => ['dotwork']])
        ->assertOk()->assertJsonPath('data.description', 'Nuova')->assertJsonPath('data.styles.0', 'dotwork');

    $foreign = Work::create(['artist_id' => Artist::factory()->create()->id, 'image_path' => 'z.jpg']);
    $this->deleteJson("/api/v1/me/works/{$foreign->id}")->assertNotFound();

    $this->deleteJson("/api/v1/me/works/{$work->id}")->assertNoContent();
    Storage::disk('public')->assertMissing($work->image_path);
    expect(Work::whereKey($work->id)->exists())->toBeFalse();
});

it('lists the portfolio in order', function () {
    $this->postJson('/api/v1/me/works', ['image' => UploadedFile::fake()->image('1.jpg', 600, 600), 'description' => 'primo']);
    $this->postJson('/api/v1/me/works', ['image' => UploadedFile::fake()->image('2.jpg', 600, 600), 'description' => 'secondo']);

    $this->getJson('/api/v1/me/works')->assertOk()
        ->assertJsonPath('data.0.description', 'primo')
        ->assertJsonPath('data.1.description', 'secondo');
});

it('requires an artist profile to upload', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/me/works', ['image' => UploadedFile::fake()->image('x.jpg', 600, 600)])
        ->assertConflict();
});
