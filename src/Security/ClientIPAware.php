<?php

namespace Veracrypt\CrashCollector\Security;

use Veracrypt\CrashCollector\Exception\AuthorizationException;

trait ClientIPAware
{
    /**
     * @return string
     * @throws \DomainException
     * @throws AuthorizationException
     */
    protected function getClientIP(): string
    {
        if (!isset($_SERVER['REMOTE_ADDR'])) {
            throw new AuthorizationException("REMOTE_ADDR is missing. Can not identify client IP");
        }

        $header = @$_ENV['CLIENT_IP_HEADER'];

        if (str_starts_with($header, 'HTTP_')) {
            $trustedProxies = array_filter(array_map('trim', explode(',', @$_ENV['TRUSTED_PROXIES'])));
            if (!$trustedProxies) {
                throw new \DomainException("A value for TRUSTED_PROXIES config is required when CLIENT_IP_HEADER is set to '$header'");
            }
            if (!in_array($_SERVER['REMOTE_ADDR'], $trustedProxies)) {
                // the request does not come from a trusted proxy - we use the REMOTE_ADDR as headers are unreliable
                $header = '';
            }
        }

        switch($header) {
            case '':
            case 'REMOTE_ADDR':
                // q: are we 100% sure this is always set?
                $ip = (string)$_SERVER['REMOTE_ADDR'];
                break;

            case 'HTTP_CLIENT_IP':
            case 'HTTP_FASTLY_CLIENT_IP':
            case 'HTTP_TRUE_CLIENT_IP':
            case 'HTTP_X_REAL_IP':
                $ip = (string)@$_SERVER[$header];
                break;

            case 'HTTP_X_FORWARDED_FOR':
                $ips = array_filter(array_map('trim', explode(',', @$_SERVER[$header])));
                $ip = $ips ? reset($ips) : '';
                break;

            default:
                throw new \DomainException("Unsupported value for config CLIENT_IP_HEADER : '{$header}'");
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new AuthorizationException("Client IP '$ip' is invalid");
        }

        return $ip;
    }
}
