@php
    $address = collect([trim(($studio->street ?? '').' '.($studio->housenumber ?? '')), trim(($studio->postcode ?? '').' '.($studio->city ?? ''))])->filter()->join(', ');
    $residents = $studio->artists->filter(fn ($a) => $a->pivot->type->value === 'resident');
    $guests = $studio->artists->filter(fn ($a) => $a->pivot->type->value === 'guest');
@endphp
@extends('layouts.public', [
    'title' => $studio->name.($studio->city ? ' – studio di tatuaggi a '.$studio->city : ''),
    'description' => trim('Studio di tatuaggi'.($address ? ', '.$address : '').'. '.($residents->isNotEmpty() ? 'Artisti: '.$residents->pluck('name')->join(', ').'.' : '')),
    'url' => $url,
    'jsonLd' => $jsonLd,
    'map' => [['lat' => $studio->location->lat, 'lng' => $studio->location->lng, 'name' => $studio->name]],
    'noindex' => $noindex,
])

@section('content')
    <h1>{{ $studio->name }}</h1>
    @if ($address)<div class="meta">{{ $address }}</div>@endif

    <div class="actions">
        <a class="cta" href="{{ $appUrl }}">Apri nell'app</a>
        <a class="cta secondary" href="https://www.openstreetmap.org/directions?to={{ $studio->location->lat }}%2C{{ $studio->location->lng }}" rel="noopener">Indicazioni</a>
        @if ($studio->phone)<a class="cta secondary" href="tel:{{ preg_replace('/\s+/', '', $studio->phone) }}">Chiama</a>@endif
        @if ($studio->website)<a class="cta secondary" href="{{ $studio->website }}" rel="noopener nofollow">Sito web</a>@endif
        @if ($studio->instagram_url)<a class="cta secondary" href="{{ $studio->instagram_url }}" rel="noopener nofollow">Instagram</a>@endif
    </div>

    @if ($studio->opening_hours)
        <h2>Orari</h2>
        <p>{{ $studio->opening_hours }}</p>
    @endif

    <h2>Artisti</h2>
    @if ($residents->isEmpty())
        <p class="meta">Nessun artista ha ancora collegato il proprio profilo a questo studio. Lavori qui? Rivendica lo studio dall'app Tatlas.</p>
    @else
        <div class="list">
            @foreach ($residents as $a)
                <div><a href="{{ \App\Http\Controllers\Web\PublicPageController::artistUrl($a) }}">{{ $a->name }}</a><span class="meta">{{ $a->styles->pluck('name_it')->take(3)->join(', ') }}</span></div>
            @endforeach
        </div>
    @endif

    @if ($guests->isNotEmpty())
        <h2>Guest spot</h2>
        <div class="list">
            @foreach ($guests as $a)
                <div><a href="{{ \App\Http\Controllers\Web\PublicPageController::artistUrl($a) }}">{{ $a->name }}</a>
                    <span class="guest">{{ $a->pivot->starts_at?->translatedFormat('j M') }}@if ($a->pivot->ends_at) – {{ $a->pivot->ends_at->translatedFormat('j M') }}@endif</span></div>
            @endforeach
        </div>
    @endif

    <h2>Mappa</h2>
    <div id="map" role="img" aria-label="Posizione dello studio"></div>
    @if ($isOsm)
        <p class="meta">Dati dello studio da <a href="https://www.openstreetmap.org/{{ $studio->osm_type }}/{{ $studio->osm_id }}" rel="noopener">OpenStreetMap</a>, © OpenStreetMap contributors (ODbL).</p>
    @endif
    <p class="meta" style="margin-top:32px"><a href="{{ route('public.removal', ['tipo' => 'studio', 'id' => $studio->id]) }}">Segnala un errore o chiedi la rimozione</a></p>
@endsection
