{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($artists as $artist)
    <url><loc>{{ \App\Http\Controllers\Web\PublicPageController::artistUrl($artist) }}</loc><lastmod>{{ $artist->updated_at?->toDateString() }}</lastmod></url>
@endforeach
@foreach ($studios as $studio)
    <url><loc>{{ \App\Http\Controllers\Web\PublicPageController::studioUrl($studio) }}</loc><lastmod>{{ $studio->updated_at?->toDateString() }}</lastmod></url>
@endforeach
</urlset>
