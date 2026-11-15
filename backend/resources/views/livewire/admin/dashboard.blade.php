<div>
    <nav class="tabs">
        @foreach (['artists' => 'Artisti da verificare', 'claims' => 'Rivendicazioni', 'removals' => 'Richieste di rimozione'] as $key => $label)
            <a href="?tab={{ $key }}" wire:click.prevent="$set('tab', '{{ $key }}')" class="tab {{ $tab === $key ? 'active' : '' }}">
                {{ $label }} @if ($counts[$key] > 0)<span class="badge">{{ $counts[$key] }}</span>@endif
            </a>
        @endforeach
    </nav>

    @if ($flash)
        <div class="flash">{{ $flash }}</div>
    @endif

    @if ($tab === 'artists')
        @forelse ($artists as $artist)
            <div class="card" wire:key="artist-{{ $artist->id }}">
                <h3>{{ $artist->name }} <span class="meta">/{{ $artist->slug }}</span></h3>
                <div class="meta">
                    Account: {{ $artist->owner?->email ?? '—' }} · creato {{ $artist->created_at?->diffForHumans() }}
                    · {{ $artist->works_count }} lavori
                </div>
                <div class="meta">Stili: {{ $artist->styles->pluck('name_it')->join(', ') ?: '—' }}</div>
                <div class="meta">Studi: {{ $artist->studios->map(fn ($s) => $s->name.' ('.$s->pivot->type->value.')')->join(', ') ?: '—' }}</div>
                @if ($artist->instagram_url)
                    <div class="meta"><a href="{{ $artist->instagram_url }}" target="_blank" rel="noopener">{{ $artist->instagram_url }}</a></div>
                @endif
                @if ($artist->bio)<p>{{ $artist->bio }}</p>@endif
                <div class="row">
                    <button class="btn ok" wire:click="verifyArtist({{ $artist->id }})">Verifica e pubblica</button>
                    <button class="btn bad" wire:click="hideArtist({{ $artist->id }})" wire:confirm="Nascondere questo profilo?">Nascondi</button>
                </div>
            </div>
        @empty
            <div class="empty">Nessun profilo in attesa di verifica.</div>
        @endforelse
    @endif

    @if ($tab === 'claims')
        @forelse ($claims as $claim)
            <div class="card" wire:key="claim-{{ $claim->id }}">
                <h3>{{ $claim->claimable?->name ?? 'Elemento rimosso' }} <span class="meta">({{ $claim->claimable_type }} #{{ $claim->claimable_id }})</span></h3>
                <div class="meta">Richiesta da {{ $claim->user->name }} &lt;{{ $claim->user->email }}&gt; · {{ $claim->created_at?->diffForHumans() }}</div>
                @if ($claim->message)<p>{{ $claim->message }}</p>@endif
                @if ($claim->evidence_url)
                    <div class="meta">Prova: <a href="{{ $claim->evidence_url }}" target="_blank" rel="noopener">{{ $claim->evidence_url }}</a></div>
                @endif
                <div class="row">
                    <button class="btn ok" wire:click="approveClaim({{ $claim->id }})">Approva</button>
                    <input type="text" placeholder="Motivo del rifiuto" wire:model="reasons.{{ $claim->id }}">
                    <button class="btn bad" wire:click="rejectClaim({{ $claim->id }})">Rifiuta</button>
                </div>
                @error('reasons.'.$claim->id)<div class="error">{{ $message }}</div>@enderror
            </div>
        @empty
            <div class="empty">Nessuna rivendicazione in attesa.</div>
        @endforelse
    @endif

    @if ($tab === 'removals')
        @forelse ($removals as $removal)
            <div class="card" wire:key="removal-{{ $removal->id }}">
                <h3>{{ $removal->subject?->name ?? ($removal->subject_type ? 'Elemento non trovato' : 'Richiesta generica') }}
                    @if ($removal->subject_type)<span class="meta">({{ $removal->subject_type }} #{{ $removal->subject_id }})</span>@endif
                </h3>
                <div class="meta">Da {{ $removal->requester_name }} &lt;{{ $removal->requester_email }}&gt; · {{ $removal->created_at?->diffForHumans() }}</div>
                <p>{{ $removal->reason }}</p>
                <div class="row">
                    @if ($removal->subject)
                        <button class="btn ok" wire:click="acceptRemoval({{ $removal->id }})" wire:confirm="Nascondere il contenuto e chiudere la richiesta?">Nascondi contenuto</button>
                    @endif
                    <button class="btn plain" wire:click="rejectRemoval({{ $removal->id }})">Respingi</button>
                </div>
            </div>
        @empty
            <div class="empty">Nessuna richiesta di rimozione.</div>
        @endforelse
    @endif
</div>
