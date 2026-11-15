@extends('layouts.public', [
    'title' => 'Nuova password',
    'description' => 'Imposta una nuova password per il tuo account Tatlas.',
    'url' => \App\Http\Controllers\Web\PublicPageController::base().'/reset-password',
    'noindex' => true,
])

@section('content')
    <h1>Imposta una nuova password</h1>
    <form method="post" action="{{ route('password.update') }}" style="display:grid;gap:12px;max-width:420px">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label>Email<br><input type="email" name="email" value="{{ old('email', $email) }}" required style="width:100%;padding:8px"></label>
        <label>Nuova password (almeno 8 caratteri)<br><input type="password" name="password" required minlength="8" style="width:100%;padding:8px"></label>
        <label>Ripeti la password<br><input type="password" name="password_confirmation" required minlength="8" style="width:100%;padding:8px"></label>
        @if ($errors->any())<p style="color:#b91c1c">{{ $errors->first() }}</p>@endif
        <button class="cta" type="submit" style="border:0;cursor:pointer">Salva la nuova password</button>
    </form>
@endsection
