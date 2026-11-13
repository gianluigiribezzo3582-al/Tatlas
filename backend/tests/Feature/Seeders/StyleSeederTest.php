<?php

use App\Models\Style;
use Database\Seeders\StyleSeeder;

it('seeds the taxonomy with parents and children', function () {
    $this->seed(StyleSeeder::class);

    $blackwork = Style::where('slug', 'blackwork')->firstOrFail();

    expect($blackwork->children->pluck('slug')->all())
        ->toBe(['dotwork', 'geometric', 'ornamental', 'tribal'])
        ->and(Style::where('slug', 'micro-realism')->first()->parent->slug)->toBe('realism')
        ->and(Style::where('slug', 'watercolor')->first()->name('it'))->toBe('Acquerello');
});

it('is idempotent', function () {
    $this->seed(StyleSeeder::class);
    $count = Style::count();

    $this->seed(StyleSeeder::class);

    expect(Style::count())->toBe($count)->and($count)->toBeGreaterThan(15);
});

it('stores lowercase synonyms', function () {
    $this->seed(StyleSeeder::class);

    expect(Style::where('slug', 'black-and-grey')->first()->synonyms)->toContain('bianco e nero', 'b&g');
});

it('uses unique slugs and synonyms across the whole taxonomy', function () {
    $this->seed(StyleSeeder::class);

    $synonyms = Style::all()->flatMap->synonyms;

    expect($synonyms->duplicates()->all())->toBe([]);
});
