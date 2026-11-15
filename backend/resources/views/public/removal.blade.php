@extends('layouts.public', [
    'title' => 'Richiesta di rimozione',
    'description' => 'Chiedi la rimozione di uno studio, di un profilo o di una foto da Tatlas.',
    'url' => \App\Http\Controllers\Web\PublicPageController::base().'/rimozione',
    'noindex' => true,
])

@section('content')
    <h1>Richiesta di rimozione</h1>
    @if (session('sent'))
        <p><strong>Richiesta ricevuta.</strong> La esamineremo il prima possibile e ti risponderemo all'indirizzo email indicato.</p>
    @else
        <p>Usa questo modulo se un contenuto su Tatlas ti riguarda e vuoi che venga rimosso o corretto: uno studio chiuso, un profilo che non hai creato tu, una foto in cui compari. Non serve un account.</p>
        @if ($subject)
            <p class="meta">Contenuto segnalato: <strong>{{ $subject->name ?? 'foto #'.$subject->id }}</strong></p>
        @endif
        <form method="post" action="{{ route('public.removal.store') }}" style="display:grid;gap:12px;max-width:520px">
            @csrf
            <input type="hidden" name="subject_type" value="{{ $type }}">
            <input type="hidden" name="subject_id" value="{{ $id }}">
            <div style="position:absolute;left:-9999px" aria-hidden="true"><label>Lascia vuoto <input name="website_url" tabindex="-1" autocomplete="off"></label></div>
            <label>Nome e cognome<br><input name="requester_name" value="{{ old('requester_name') }}" required maxlength="120" style="width:100%;padding:8px"></label>
            <label>Email<br><input type="email" name="requester_email" value="{{ old('requester_email') }}" required style="width:100%;padding:8px"></label>
            <label>Motivo della richiesta<br><textarea name="reason" required minlength="10" maxlength="2000" rows="5" style="width:100%;padding:8px">{{ old('reason') }}</textarea></label>
            @if ($errors->any())<p style="color:#b91c1c">{{ $errors->first() }}</p>@endif
            <button class="cta" type="submit" style="border:0;cursor:pointer">Invia richiesta</button>
        </form>
    @endif
@endsection
