<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title ?? 'Tatlas · Admin' }}</title>
    <style>
        :root { --ink:#111; --muted:#6b6b6b; --line:#e4e4e4; --paper:#fff; --surface:#f6f5f2; --accent:#c2410c; --ok:#15803d; --bad:#b91c1c; }
        * { box-sizing: border-box; }
        body { margin:0; font: 15px/1.45 system-ui, -apple-system, Segoe UI, Roboto, sans-serif; color:var(--ink); background:var(--surface); }
        header { background:var(--ink); color:var(--paper); padding:12px 20px; display:flex; justify-content:space-between; align-items:center; }
        header a, header button { color:var(--paper); background:none; border:0; font:inherit; cursor:pointer; text-decoration:underline; }
        main { max-width:1000px; margin:0 auto; padding:20px 16px; }
        .tabs { display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; }
        .tab { padding:8px 14px; border:1px solid var(--line); border-radius:18px; background:var(--paper); cursor:pointer; text-decoration:none; color:var(--ink); }
        .tab.active { background:var(--ink); color:var(--paper); border-color:var(--ink); }
        .badge { display:inline-block; min-width:20px; padding:0 6px; border-radius:10px; background:var(--accent); color:#fff; font-size:12px; text-align:center; }
        .card { background:var(--paper); border:1px solid var(--line); border-radius:10px; padding:14px 16px; margin-bottom:12px; }
        .card h3 { margin:0 0 4px; font-size:17px; }
        .meta { color:var(--muted); font-size:13px; }
        .row { display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-top:10px; }
        button.btn { border:0; border-radius:8px; padding:7px 12px; font:inherit; cursor:pointer; }
        .ok { background:var(--ok); color:#fff; } .bad { background:var(--bad); color:#fff; } .plain { background:var(--surface); }
        input[type=text], input[type=email], input[type=password] { padding:8px 10px; border:1px solid var(--line); border-radius:8px; font:inherit; min-width:240px; }
        .flash { background:#ecfdf5; border:1px solid #a7f3d0; padding:10px 12px; border-radius:8px; margin-bottom:12px; }
        .error { color:var(--bad); font-size:13px; }
        .empty { color:var(--muted); padding:24px; text-align:center; }
    </style>
</head>
<body>
<header>
    <strong>Tatlas · Admin</strong>
    @auth
        <form method="post" action="{{ route('admin.logout') }}">@csrf<button type="submit">Esci ({{ auth()->user()->email }})</button></form>
    @endauth
</header>
<main>
    {{ $slot ?? '' }}
    @yield('content')
</main>
</body>
</html>
