<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['requester_name', 'requester_email', 'subject_type', 'subject_id', 'reason'])]
class RemovalRequest extends Model
{
    protected function casts(): array
    {
        return ['status' => RequestStatus::class, 'handled_at' => 'datetime'];
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
