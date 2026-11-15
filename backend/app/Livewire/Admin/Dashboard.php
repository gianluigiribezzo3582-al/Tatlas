<?php

namespace App\Livewire\Admin;

use App\Actions\Admin\HandleRemoval;
use App\Actions\Admin\ReviewClaim;
use App\Enums\RequestStatus;
use App\Models\Artist;
use App\Models\ClaimRequest;
use App\Models\RemovalRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Tatlas · Admin')]
class Dashboard extends Component
{
    #[Url]
    public string $tab = 'artists';

    /** @var array<int, string> rejection reason per claim id */
    public array $reasons = [];

    public ?string $flash = null;

    public function mount(): void
    {
        Gate::authorize('admin');
    }

    public function verifyArtist(int $id): void
    {
        Gate::authorize('admin');
        Artist::findOrFail($id)->forceFill(['is_verified' => true, 'verified_at' => now(), 'hidden_at' => null])->save();
        $this->flash = 'Artista verificato e pubblicato.';
    }

    public function hideArtist(int $id): void
    {
        Gate::authorize('admin');
        Artist::findOrFail($id)->forceFill(['hidden_at' => now()])->save();
        $this->flash = 'Profilo nascosto.';
    }

    public function approveClaim(int $id, ReviewClaim $review): void
    {
        Gate::authorize('admin');
        $review->approve(ClaimRequest::findOrFail($id), auth()->user());
        $this->flash = 'Rivendicazione approvata.';
    }

    public function rejectClaim(int $id, ReviewClaim $review): void
    {
        Gate::authorize('admin');
        $reason = trim($this->reasons[$id] ?? '');
        if ($reason === '') {
            $this->addError("reasons.{$id}", 'Indica il motivo del rifiuto.');

            return;
        }
        $review->reject(ClaimRequest::findOrFail($id), auth()->user(), $reason);
        unset($this->reasons[$id]);
        $this->flash = 'Rivendicazione rifiutata.';
    }

    public function acceptRemoval(int $id, HandleRemoval $handle): void
    {
        Gate::authorize('admin');
        $handle->accept(RemovalRequest::findOrFail($id), auth()->user());
        $this->flash = 'Contenuto nascosto, richiesta chiusa.';
    }

    public function rejectRemoval(int $id, HandleRemoval $handle): void
    {
        Gate::authorize('admin');
        $handle->reject(RemovalRequest::findOrFail($id), auth()->user());
        $this->flash = 'Richiesta respinta.';
    }

    public function render(): View
    {
        return view('livewire.admin.dashboard', [
            'counts' => [
                'artists' => Artist::where('is_verified', false)->whereNull('hidden_at')->count(),
                'claims' => ClaimRequest::where('status', RequestStatus::Pending)->count(),
                'removals' => RemovalRequest::where('status', RequestStatus::Pending)->count(),
            ],
            'artists' => $this->tab === 'artists'
                ? Artist::where('is_verified', false)->whereNull('hidden_at')
                    ->with(['owner', 'styles', 'studios'])->withCount('works')->oldest()->limit(50)->get()
                : collect(),
            'claims' => $this->tab === 'claims'
                ? ClaimRequest::where('status', RequestStatus::Pending)->with(['user', 'claimable'])->oldest()->limit(50)->get()
                : collect(),
            'removals' => $this->tab === 'removals'
                ? RemovalRequest::where('status', RequestStatus::Pending)->with('subject')->oldest()->limit(50)->get()
                : collect(),
        ]);
    }
}
