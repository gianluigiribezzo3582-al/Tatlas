@extends('layouts.admin')

@section('content')
    <div class="card" style="max-width:420px;margin:40px auto">
        <h3>Accesso amministratori</h3>
        <form method="post" action="{{ route('admin.login') }}">
            @csrf
            <p><input type="email" name="email" value="{{ old('email') }}" placeholder="Email" required autofocus></p>
            <p><input type="password" name="password" placeholder="Password" required></p>
            @error('email')<p class="error">{{ $message }}</p>@enderror
            <button class="btn ok" type="submit">Entra</button>
        </form>
    </div>
@endsection
