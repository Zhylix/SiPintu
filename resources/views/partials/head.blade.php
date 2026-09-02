@php
    $siteLogoUrl = \App\Models\Setting::getLogoUrl();
@endphp
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<!-- Website Logo favicon for browser tab -->
<link rel="icon" href="{{ $siteLogoUrl }}" sizes="any">
<link rel="shortcut icon" href="{{ $siteLogoUrl }}">
<link rel="apple-touch-icon" href="{{ $siteLogoUrl }}">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
