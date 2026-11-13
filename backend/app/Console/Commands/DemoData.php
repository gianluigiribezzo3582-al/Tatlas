<?php

namespace App\Console\Commands;

use App\Enums\StudioSource;
use App\Models\Artist;
use App\Models\Studio;
use App\Models\Style;
use App\Models\Work;
use App\Support\Geo\Point;
use App\Support\Slugger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Fictional artists for local testing only. Every demo record has a slug starting
 * with "demo-" and can be removed with --purge.
 */
class DemoData extends Command
{
    protected $signature = 'tatlas:demo-data
        {--artists=30 : Number of demo artists}
        {--purge : Delete all demo data instead}';

    protected $description = 'Create (or purge) fictional demo artists, guest spots and portfolio images';

    private const FIRST = ['Alba', 'Bruno', 'Chiara', 'Dario', 'Elena', 'Fabio', 'Giada', 'Ivo', 'Luna', 'Marco',
        'Nina', 'Oscar', 'Paola', 'Rocco', 'Sara', 'Tito', 'Vera', 'Zeno', 'Ada', 'Leo'];

    private const SUFFIX = ['Ink', 'Tattoo', 'Needle', 'Lines', 'Dots', 'Black', 'Art', 'Skin'];

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Demo data is disabled in production.');

            return self::FAILURE;
        }

        return $this->option('purge') ? $this->purge() : $this->create((int) $this->option('artists'));
    }

    private function create(int $count): int
    {
        $styles = Style::pluck('id')->all();
        if ($styles === []) {
            $this->error('Run the StyleSeeder first: php artisan db:seed');

            return self::FAILURE;
        }

        $studios = Studio::visible()->get();
        if ($studios->isEmpty()) {
            $this->warn('No studios found (OSM import not run?): creating 8 demo studios around Bari.');
            $studios = collect(range(1, 8))->map(fn ($i) => $this->demoStudio($i));
        }

        for ($i = 0; $i < $count; $i++) {
            $name = self::FIRST[array_rand(self::FIRST)].' '.self::SUFFIX[array_rand(self::SUFFIX)];
            $artist = Artist::create([
                'name' => $name,
                'slug' => Slugger::unique(Artist::class, 'demo-'.$name),
                'bio' => 'Profilo dimostrativo con dati fittizi, creato per i test.',
                'languages' => fake()->randomElement([['it'], ['it', 'en'], ['it', 'en', 'es']]),
                'accepts_walk_in' => fake()->boolean(),
                'price_range' => fake()->numberBetween(1, 3),
                'instagram_url' => null,
            ]);

            $artist->styles()->attach(
                collect(fake()->randomElements($styles, fake()->numberBetween(1, 3)))
                    ->mapWithKeys(fn ($id) => [$id => ['weight' => fake()->randomFloat(2, 0.5, 1)]])
                    ->all(),
            );

            $home = $studios->random();
            $artist->studios()->attach($home, ['type' => 'resident']);

            if (fake()->boolean(40)) {
                $start = now()->addDays(fake()->numberBetween(-2, 45))->startOfDay();
                $others = $studios->where('id', '!=', $home->id);
                $artist->studios()->attach($others->isEmpty() ? $home : $others->random(), [
                    'type' => 'guest',
                    'starts_at' => $start,
                    'ends_at' => $start->copy()->addDays(fake()->numberBetween(1, 5)),
                ]);
            }

            foreach (range(0, fake()->numberBetween(1, 3)) as $position) {
                $this->demoWork($artist, $position);
            }
        }

        $this->info("Created {$count} demo artists.");

        return self::SUCCESS;
    }

    private function demoStudio(int $i): Studio
    {
        $name = "Demo Studio {$i}";

        return Studio::create([
            'name' => $name,
            'slug' => Slugger::unique(Studio::class, 'demo-'.$name),
            'location' => new Point(lat: fake()->randomFloat(5, 41.08, 41.14), lng: fake()->randomFloat(5, 16.82, 16.92)),
            'city' => 'Bari',
            'country_code' => 'IT',
            'source' => StudioSource::User,
        ]);
    }

    /** Abstract black-ink placeholder drawn with GD (no external images). */
    private function demoWork(Artist $artist, int $position): void
    {
        $size = 600;
        $img = imagecreatetruecolor($size, $size);
        imagefill($img, 0, 0, imagecolorallocate($img, 242, 238, 230));
        $ink = imagecolorallocate($img, 20, 20, 20);
        imagesetthickness($img, 4);
        for ($k = 0; $k < 12; $k++) {
            $r = random_int(20, 220);
            match (random_int(0, 2)) {
                0 => imageellipse($img, random_int(100, 500), random_int(100, 500), $r, $r, $ink),
                1 => imageline($img, random_int(0, $size), random_int(0, $size), random_int(0, $size), random_int(0, $size), $ink),
                default => imagefilledellipse($img, random_int(100, 500), random_int(100, 500), (int) ($r / 6), (int) ($r / 6), $ink),
            };
        }
        ob_start();
        imagejpeg($img, null, 80);
        $jpeg = ob_get_clean();

        $path = "works/demo/{$artist->slug}-{$position}.jpg";
        Storage::disk('public')->put($path, $jpeg);

        Work::create([
            'artist_id' => $artist->id,
            'image_path' => $path,
            'width' => $size,
            'height' => $size,
            'description' => 'Immagine dimostrativa',
            'is_color' => false,
            'position' => $position,
        ]);
    }

    private function purge(): int
    {
        $artists = Artist::where('slug', 'like', 'demo-%')->count();
        Artist::where('slug', 'like', 'demo-%')->delete(); // cascades to works, presences, styles
        Studio::where('slug', 'like', 'demo-%')->where('source', StudioSource::User)->delete();
        Storage::disk('public')->deleteDirectory('works/demo');

        $this->info("Removed {$artists} demo artists.");

        return self::SUCCESS;
    }
}
