<?php

namespace Osiset\ShopifyApp\Objects\Values;

use Assert\AssertionFailedException;
use Funeralzone\ValueObjects\Scalars\StringTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Osiset\ShopifyApp\Contracts\Objects\Values\ShopDomain as ShopDomainValue;
use Osiset\ShopifyApp\Objects\Enums\DataSource;
use Osiset\ShopifyApp\Util;

/**
 * Value object for shop's domain.
 */
final class ShopDomain implements ShopDomainValue
{
    use StringTrait;

    /**
     * Constructor.
     *
     * @param string $domain The shop's domain.
     *
     * @return void
     */
    public function __construct(string $domain)
    {
        $this->string = $this->sanitizeShopDomain($domain);
    }

    /**
     * Grab the shop, if present, and how it was found.
     * Order of precedence is:.
     *
     *  - GET/POST Variable ("shop" or "shopDomain")
     *  - Headers ("X-Shop-Domain")
     *  - Referer ("shop" or "shopDomain" query param or decoded "token" query param)
     *
     * @param Request $request The request object.
     *
     * @return ShopDomainValue
     */
    public static function fromRequest(Request $request): ShopDomainValue
    {
        // All possible methods
        $options = [
            // GET/POST
            DataSource::INPUT()->toNative() => $request->input('shop', $request->input('shopDomain')),

            // Headers
            DataSource::HEADER()->toNative() => $request->header('X-Shop-Domain'),

            // Headers: Referer
            DataSource::REFERER()->toNative() => function () use ($request): ?string {
                $url = parse_url($request->header('referer'), PHP_URL_QUERY);
                if (! $url) {
                    return null;
                }

                $params = Util::parseQueryString($url);
                $shop = Arr::get($params, 'shop', Arr::get($params, 'shopDomain'));
                if ($shop) {
                    return $shop;
                }

                $token = Arr::get($params, 'token');
                if ($token) {
                    try {
                        $token = new SessionToken($token, false);
                        if ($shopDomain = $token->getShopDomain()) {
                            return $shopDomain->toNative();
                        }
                    } catch (AssertionFailedException $e) {
                        // Unable to decode the token
                        return null;
                    }
                }

                return null;
            },
        ];

        // Loop through each until we find the shop
        foreach ($options as $value) {
            $result = is_callable($value) ? $value() : $value;
            if ($result !== null) {
                // Found a shop
                return self::fromNative($result);
            }
        }

        // No shop domain found in any source
        return NullShopDomain::fromNative(null);
    }

    /**
     * Ensures shop domain meets the specs.
     *
     * @param string $domain The shopify domain
     *
     * @return string
     */
    protected function sanitizeShopDomain(string $domain): ?string
    {
        $configEndDomain = Util::getShopifyConfig('myshopify_domain');
        $domain = strtolower(preg_replace('/https?:\/\//i', '', trim($domain)));

        if (strpos($domain, $configEndDomain) === false && strpos($domain, '.') === false) {
            // No myshopify.com ($configEndDomain) in shop's name
            $domain .= ".{$configEndDomain}";
        }

        // Return the host after cleaned up
        return parse_url("https://{$domain}", PHP_URL_HOST);
    }
}
