@php
    $styleNames = $artist->styles->pluck('name_it');
    $first = $artist->works->first();
@endphp
@extends('layouts.public', [
    'title' => $artist->name.' – tatuatore'.($artist->studios->first()?->city ? ' a '.$artist->studios->first()->city : ''),
    'description' => \Illuminate\Support\Str::limit(trim(($styleNames->isNotEmpty() ? $styleNames->join(', ').'. ' : '').($artist->bio ?? '')), 155) ?: 'Profilo del tatuatore '.$artist->name.' su Tatlas.',
    'url' => $url,
    'ogType' => 'profile',
    'image' => $first ? \App\Http\Resources\V1\WorkResource::url($first->image_path) : null,
    'jsonLd' => $jsonLd,
    'map' => $mapPoints,
    'noindex' => $noindex,
])

@section('content')
    <h1>{{ $artist->name }} @if ($artist->is_verified)<span title="Profilo verificato">✓</span>@endif</h1>
    <div class="meta">
        {{ collect([
            $artist->accepts_walk_in ? 'Accetta walk-in' : null,
            $artist->price_range ? str_repeat('€', $artist->price_range) : null,
            $artist->languages ? strtoupper(implode(' · ', $artist->languages)) : null,
        ])->filter()->join('  ·  ') }}
    </div>
    @if ($styleNames->isNotEmpty())
        <div class="chips">@foreach ($styleNames as $s)<span class="chip">{{ $s }}</span>@endforeach</div>
    @endif

    <div class="actions">
        <a class="cta" href="{{ $appUrl }}">Apri nell'app</a>
        @if ($artist->instagram_url)<a class="cta secondary" href="{{ $artist->instagram_url }}" rel="noopener nofollow">Instagram</a>@endif
        @if ($artist->website)<a class="cta secondary" href="{{ $artist->website }}" rel="noopener nofollow">Sito web</a>@endif
    </div>

    @if ($artist->bio)
        <h2>Chi sono</h2>
        <p>{!! nl2br(e($artist->bio)) !!}</p>
    @endif

    @if ($artist->works->isNotEmpty())
        <h2>Portfolio</h2>
        <div class="grid">
            @foreach ($artist->works as $work)
                <img src="{{ \App\Http\Resources\V1\WorkResource::url($work->thumbnail_path ?? $work->image_path) }}"
                     alt="{{ $work->description ?: 'Tatuaggio di '.$artist->name }}" loading="lazy" width="480" height="480">
            @endforeach
        </div>
    @endif

    @if ($artist->studios->isNotEmpty())
        <h2>Dove trovarlo</h2>
        <div class="list">
            @foreach ($artist->studios as $studio)
                <div>
                    <span>
                        <a href="{{ \App\Http\Controllers\Web\PublicPageController::studioPath($studio) }}">{{ $studio->name }}</a><br>
                        <span class="meta">{{ collect([trim(($studio->street ?? '').' '.($studio->housenumber ?? '')), trim(($studio->postcode ?? '').' '.($studio->city ?? ''))])->filter()->join(', ') }}</span>
                    </span>
                    @if ($studio->pivot->type->value === 'guest')
                        <span class="guest">Guest {{ $studio->pivot->starts_at?->translatedFormat('j M') }}@if ($studio->pivot->ends_at) – {{ $studio->pivot->ends_at->translatedFormat('j M') }}@endif</span>
                    @else
                        <span class="meta">Resident</span>
                    @endif
                </div>
            @endforeach
        </div>
        <h2>Mappa</h2>
        <div id="map" role="img" aria-label="Mappa degli studi"></div>
    @endif
    <p class="meta" style="margin-top:32px"><a href="{{ route('public.removal', ['tipo' => 'artist', 'id' => $artist->id]) }}">Segnala un errore o chiedi la rimozione</a></p>
@endsection
