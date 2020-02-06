<?php

namespace OhMyBrew\ShopifyApp;

/**
 * HMAC creation helper.
 *
 * @param array $opts The options for building the HMAC
 *
 * @return string
 */
function createHmac(array $opts): string
{
    // Setup defaults
    $data = $opts['data'];
    $raw = $opts['raw'] ?? false;
    $buildQuery = $opts['buildQuery'] ?? false;
    $buildQueryWithJoin = $opts['buildQueryWithJoin'] ?? false;
    $encode = $opts['encode'] ?? false;
    $secret = $opts['secret'];

    if ($buildQuery) {
        //Query params must be sorted and compiled
        ksort($data);
        $queryCompiled = [];
        foreach ($data as $key => $value) {
            $queryCompiled[] = "{$key}=".(is_array($value) ? implode(',', $value) : $value);
        }
        $data = implode(
            ($buildQueryWithJoin ? '&' : ''),
            $queryCompiled
        );
    }

    // Create the hmac all based on the secret
    $hmac = hash_hmac('sha256', $data, $secret, $raw);

    // Return based on options
    return $encode ? base64_encode($hmac) : $hmac;
}
