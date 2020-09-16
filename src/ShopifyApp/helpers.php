<?php

namespace Osiset\ShopifyApp;

/**
 * HMAC creation helper.
 *
 * @param array  $opts   The options for building the HMAC.
 * @param string $secret The app secret key.
 *
 * @return string
 */
function createHmac(array $opts, string $secret): string
{
    // Setup defaults
    $data = $opts['data'];
    $raw = $opts['raw'] ?? false;
    $buildQuery = $opts['buildQuery'] ?? false;
    $buildQueryWithJoin = $opts['buildQueryWithJoin'] ?? false;
    $encode = $opts['encode'] ?? false;

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

/**
 * Parse query strings the same way as Rack::Until in Ruby. (This is a port from Rack 2.3.0.).
 *
 * From Shopify's docs, they use Rack::Util.parse_query, which does *not* parse array parameters properly.
 * Array parameters such as `name[]=value1&name[]=value2` becomes `['name[]' => ['value1', 'value2']] in Shopify.
 * See: https://github.com/rack/rack/blob/f9ad97fd69a6b3616d0a99e6bedcfb9de2f81f6c/lib/rack/query_parser.rb#L36
 *
 * @param string $qs The query string.
 * @param string $d  The delimiter.
 *
 * @return mixed
 */
function parseQueryString(string $qs, string $d = null): array
{
    $COMMON_SEP = [';' => '/[;]\s*/', ';,' => '/[;,]\s*/', '&' => '/[&]\s*/'];
    $DEFAULT_SEP = '/[&;]\s*/';

    $params = [];
    $split = preg_split($d ? ($COMMON_SEP[$d] || '/['.$d.']\s*/') : $DEFAULT_SEP, $qs ?? '');

    foreach ($split as $p) {
        if (! $p) {
            continue;
        }

        [$k, $v] = strpos($p, '=') !== false ? explode('=', $p, 2) : [$p, null];

        $k = urldecode($k);
        $v = $v !== null ? urldecode($v) : $v;

        if (isset($params[$k])) {
            $cur = $params[$k];

            if (is_array($cur)) {
                $params[$k][] = $v;
            } else {
                $params[$k] = [$cur, $v];
            }
        } else {
            $params[$k] = $v;
        }
    }

    return $params;
}
