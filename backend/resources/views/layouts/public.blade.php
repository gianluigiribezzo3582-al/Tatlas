<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · Tatlas</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $url }}">
    @if (!empty($noindex))<meta name="robots" content="noindex">@endif
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:site_name" content="Tatlas">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $url }}">
    @if (!empty($image))<meta property="og:image" content="{{ $image }}">@endif
    <meta name="twitter:card" content="{{ !empty($image) ? 'summary_large_image' : 'summary' }}">
    @if (!empty($jsonLd))
        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endif
    @if (!empty($map))
        <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css">
    @endif
    <style>
        :root { --ink:#111; --muted:#6b6b6b; --line:#e4e4e4; --paper:#fff; --surface:#f6f5f2; --accent:#c2410c; --guest:#7c3aed; }
        * { box-sizing: border-box; }
        body { margin:0; font:16px/1.5 system-ui,-apple-system,Segoe UI,Roboto,sans-serif; color:var(--ink); background:var(--paper); }
        a { color:var(--accent); }
        header.site { display:flex; justify-content:space-between; align-items:center; padding:12px 16px; border-bottom:1px solid var(--line); }
        header.site a.brand { font-weight:800; font-size:20px; color:var(--ink); text-decoration:none; }
        main { max-width:860px; margin:0 auto; padding:20px 16px 48px; }
        h1 { font-size:30px; line-height:1.2; margin:0 0 6px; }
        h2 { font-size:13px; letter-spacing:.06em; text-transform:uppercase; color:var(--muted); margin:28px 0 10px; }
        .meta { color:var(--muted); font-size:14px; }
        .chips { display:flex; flex-wrap:wrap; gap:6px; margin:10px 0; }
        .chip { border:1px solid var(--line); border-radius:16px; padding:3px 10px; font-size:13px; }
        .grid { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
        .grid img { width:100%; height:auto; aspect-ratio:1; object-fit:cover; border-radius:6px; background:var(--surface); display:block; }
        .list > div { padding:10px 0; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; gap:12px; }
        .guest { color:var(--guest); font-weight:600; white-space:nowrap; }
        .cta { display:inline-block; background:var(--ink); color:var(--paper); padding:10px 16px; border-radius:8px; text-decoration:none; font-weight:600; }
        .cta.secondary { background:var(--paper); color:var(--ink); border:1px solid var(--line); }
        .actions { display:flex; gap:8px; flex-wrap:wrap; margin:16px 0; }
        #map { height:280px; border-radius:10px; overflow:hidden; background:var(--surface); }
        footer { max-width:860px; margin:0 auto; padding:16px; color:var(--muted); font-size:13px; border-top:1px solid var(--line); }
        @media (max-width:520px) { h1 { font-size:24px; } .grid { grid-template-columns:repeat(2,1fr); } }
    </style>
</head>
<body>
<header class="site">
    <a class="brand" href="{{ \App\Http\Controllers\Web\PublicPageController::base() }}/">Tatlas</a>
    <span class="meta">Trova il tatuatore giusto vicino a te</span>
</header>
<main>
    @yield('content')
</main>
<footer>
    Dati sulle mappe © <a href="https://www.openstreetmap.org/copyright" rel="noopener">OpenStreetMap contributors</a> (ODbL) · tile © OpenFreeMap ·
    <a href="{{ route('public.privacy') }}">Privacy</a> ·
    <a href="{{ route('public.removal') }}">Richiedi una rimozione</a>
</footer>
@if (!empty($map))
    <script src="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js"></script>
    <script>
        (function () {
            var points = @json($map);
            if (!points.length || !window.maplibregl) return;
            var map = new maplibregl.Map({
                container: 'map',
                style: 'https://tiles.openfreemap.org/styles/positron',
                center: [points[0].lng, points[0].lat],
                zoom: 14,
                attributionControl: { compact: true },
                cooperativeGestures: true
            });
            var bounds = new maplibregl.LngLatBounds();
            points.forEach(function (p) {
                new maplibregl.Marker({ color: '#c2410c' })
                    .setLngLat([p.lng, p.lat])
                    .setPopup(new maplibregl.Popup({ offset: 24 }).setText(p.name))
                    .addTo(map);
                bounds.extend([p.lng, p.lat]);
            });
            if (points.length > 1) map.fitBounds(bounds, { padding: 50, maxZoom: 14, duration: 0 });
        })();
    </script>
@endif
</body>
</html>
