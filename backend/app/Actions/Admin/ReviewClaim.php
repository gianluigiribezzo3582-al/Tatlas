<?php

namespace App\Actions\Admin;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\ClaimRequest;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReviewClaim
{
    public function approve(ClaimRequest $claim, User $admin): void
    {
        $this->ensurePending($claim);

        DB::transaction(function () use ($claim, $admin) {
            $claim->forceFill([
                'status' => RequestStatus::Approved,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ])->save();

            $target = $claim->claimable;
            if ($target instanceof Studio) {
                $target->forceFill(['claimed_by_user_id' => $claim->user_id])->save();

                // Other pending claims on the same studio can no longer succeed.
                ClaimRequest::where('claimable_type', 'studio')
                    ->where('claimable_id', $target->id)
                    ->where('status', RequestStatus::Pending)
                    ->update([
                        'status' => RequestStatus::Rejected->value,
                        'reviewed_by' => $admin->id,
                        'reviewed_at' => now(),
                        'rejection_reason' => 'Studio assegnato a un altro richiedente.',
                    ]);
            }

            if ($claim->user->role === UserRole::User) {
                $claim->user->forceFill(['role' => UserRole::Artist])->save();
            }
        });
    }

    public function reject(ClaimRequest $claim, User $admin, string $reason): void
    {
        $this->ensurePending($claim);
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A rejection reason is required.');
        }

        $claim->forceFill([
            'status' => RequestStatus::Rejected,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ])->save();
    }

    private function ensurePending(ClaimRequest $claim): void
    {
        if ($claim->status !== RequestStatus::Pending) {
            throw new InvalidArgumentException('Claim already reviewed.');
        }
    }
}
