@extends('layouts.public', [
    'title' => $what === 'email' ? 'Email confermata' : 'Password aggiornata',
    'description' => 'Tatlas',
    'url' => \App\Http\Controllers\Web\PublicPageController::base(),
    'noindex' => true,
])

@section('content')
    @if ($what === 'email')
        <h1>Email confermata ✓</h1>
        <p>Grazie! Puoi tornare all'app Tatlas.</p>
    @else
        <h1>Password aggiornata ✓</h1>
        <p>Per sicurezza abbiamo chiuso tutte le sessioni aperte: accedi di nuovo dall'app con la nuova password.</p>
    @endif
    <p><a class="cta" href="tatlas://account">Apri l'app</a></p>
@endsection
