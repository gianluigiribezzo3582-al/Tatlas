<?php

namespace App\Models;

use App\Enums\PresenceType;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ArtistStudio extends Pivot
{
    public $incrementing = true;

    protected $table = 'artist_studio';

    protected function casts(): array
    {
        return [
            'type' => PresenceType::class,
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }
}
