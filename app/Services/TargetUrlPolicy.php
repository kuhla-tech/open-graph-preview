<?php

namespace App\Services;

class TargetUrlPolicy
{
    public function options(string $url): array
    {
        $host = strtolower(trim(parse_url($url, PHP_URL_HOST) ?? '', '[]'));
        $port = parse_url($url, PHP_URL_PORT) ?: (strtolower(parse_url($url, PHP_URL_SCHEME) ?? '') === 'https' ? 443 : 80);
        if (config('monitoring.allow_private_targets')) {
            $local = $host === 'localhost' || str_ends_with($host, '.test') || (filter_var($host, FILTER_VALIDATE_IP) && ! $this->publicIp($host));

            return ['verify' => ! $local];
        }
        if (! in_array($port, [80, 443], true) || $host === 'localhost' || str_ends_with($host, '.test')) {
            throw new \RuntimeException(__('Die gehostete Version prüft nur öffentliche HTTP-/HTTPS-Adressen auf Port 80 oder 443.'));
        }
        $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : array_merge(gethostbynamel($host) ?: [], array_column(dns_get_record($host, DNS_AAAA) ?: [], 'ipv6'));
        if (! $addresses) {
            throw new \RuntimeException(__('Der Zielhost konnte nicht aufgelöst werden.'));
        }
        foreach ($addresses as $address) {
            if (! $this->publicIp($address)) {
                throw new \RuntimeException(__('Private oder reservierte Zieladressen sind in der gehosteten Version nicht erlaubt.'));
            }
        }
        $address = str_contains($addresses[0], ':') ? '['.$addresses[0].']' : $addresses[0];

        return ['verify' => true, 'proxy' => '', 'curl' => [CURLOPT_RESOLVE => [$host.':'.$port.':'.$address]]];
    }

    public function publicIp(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }
        if (str_starts_with(strtolower($ip), '::ffff:')) {
            return false;
        }
        if (str_starts_with(strtolower($ip), '2001:db8:') || str_starts_with(strtolower($ip), '2002:') || str_starts_with(strtolower($ip), '2001:0:')) {
            return false;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return str_starts_with($ip, '2') || str_starts_with($ip, '3');
        }
        $long = ip2long($ip);
        foreach ([['100.64.0.0', 10], ['192.0.0.0', 24], ['192.0.2.0', 24], ['198.18.0.0', 15], ['198.51.100.0', 24], ['203.0.113.0', 24], ['224.0.0.0', 4]] as [$network, $bits]) {
            $mask = -1 << (32 - $bits);
            if (($long & $mask) === (ip2long($network) & $mask)) {
                return false;
            }
        }

        return true;
    }
}
