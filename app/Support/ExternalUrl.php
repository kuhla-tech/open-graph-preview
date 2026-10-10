<?php

namespace App\Support;

class ExternalUrl
{
    public static function to(string $url, string $placement): string
    {
        $parts = parse_url($url);
        if (! $parts || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            || ($parts['host'] ?? '') === parse_url(config('app.url'), PHP_URL_HOST)) {
            return $url;
        }
        [$address, $fragment] = array_pad(explode('#', $url, 2), 2, null);
        [$path, $query] = array_pad(explode('?', $address, 2), 2, '');
        $parameters = array_filter(explode('&', $query), function (string $parameter): bool {
            $key = urldecode(explode('=', $parameter, 2)[0]);

            return $parameter !== '' && ! in_array($key, ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content'], true);
        });
        $parameters[] = http_build_query([
            'utm_source' => config('site.utm_source') ?: (parse_url(config('app.url'), PHP_URL_HOST) ?: 'open-graph-studio'),
            'utm_medium' => 'referral',
            'utm_campaign' => 'outbound_links',
            'utm_content' => $placement,
        ], '', '&', PHP_QUERY_RFC3986);

        return $path.'?'.implode('&', $parameters).($fragment !== null ? '#'.$fragment : '');
    }
}
