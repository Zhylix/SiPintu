@php
    $siteLogoUrl = \App\Models\Setting::getLogoUrl();
    $siteIconUrl = \App\Models\Setting::getIconUrl();
    $iconVersion = \App\Models\Setting::getIconVersion();
@endphp
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<!-- Website Logo & PWA Icon favicon for browser tab -->
<link rel="icon" href="{{ $siteIconUrl }}?v={{ $iconVersion }}" sizes="any">
<link rel="shortcut icon" href="{{ $siteIconUrl }}?v={{ $iconVersion }}">
<link rel="apple-touch-icon" href="{{ $siteIconUrl }}?v={{ $iconVersion }}">
@include('partials.pwa-head')

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
