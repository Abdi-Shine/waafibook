<?php

namespace App\Support;

use Illuminate\Support\Facades\URL;

class PublicUrl
{
    /**
     * Build a temporary signed URL rooted at the app's canonical APP_URL,
     * regardless of the host the current request came in on. Links meant
     * to be shared externally (e.g. via WhatsApp) must always resolve to
     * a publicly reachable domain, not a local dev host like 127.0.0.1.
     */
    public static function temporarySigned(string $routeName, \DateTimeInterface $expiration, array $parameters = []): string
    {
        $appUrl = config('app.url');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'https';

        URL::forceRootUrl($appUrl);
        URL::forceScheme($scheme);
        $url = URL::temporarySignedRoute($routeName, $expiration, $parameters);
        URL::forceRootUrl(null);
        URL::forceScheme(null);

        return $url;
    }
}
