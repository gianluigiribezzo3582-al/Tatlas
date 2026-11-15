<?php

namespace App\Actions\Admin;

use App\Enums\RequestStatus;
use App\Models\RemovalRequest;
use App\Models\User;
use InvalidArgumentException;

class HandleRemoval
{
    /** Hides the subject from every public surface (data kept for audit / undo). */
    public function accept(RemovalRequest $removal, User $admin): void
    {
        $this->ensurePending($removal);

        $removal->subject?->forceFill(['hidden_at' => now()])->save();
        $removal->forceFill([
            'status' => RequestStatus::Done,
            'handled_by' => $admin->id,
            'handled_at' => now(),
        ])->save();
    }

    public function reject(RemovalRequest $removal, User $admin): void
    {
        $this->ensurePending($removal);

        $removal->forceFill([
            'status' => RequestStatus::Rejected,
            'handled_by' => $admin->id,
            'handled_at' => now(),
        ])->save();
    }

    private function ensurePending(RemovalRequest $removal): void
    {
        if ($removal->status !== RequestStatus::Pending) {
            throw new InvalidArgumentException('Request already handled.');
        }
    }
}
