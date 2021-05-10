<?php

namespace Osiset\ShopifyApp\Objects\Values;

use Assert\AssertionFailedException;
use Funeralzone\ValueObjects\Scalars\StringTrait;
use Illuminate\Http\Request;
use Osiset\ShopifyApp\Contracts\Objects\Values\ShopDomain as ShopDomainValue;
use Osiset\ShopifyApp\Exceptions\MissingShopDomainException;
use function Osiset\ShopifyApp\getShopifyConfig;
use function Osiset\ShopifyApp\parseQueryString;

/**
 * Value object for shop's domain.
 */
final class ShopDomain implements ShopDomainValue
{
    use StringTrait;

    /**
     * Contructor.
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
     * Find the shop domain in the given request.
     * If the request inputs contain "shop" or "shopDomain", it is returned.
     * If the request inputs contain "token", it is decoded and checked for a shop domain.
     * If the request has a referrer URL, the same checks are performed against the referrer URL.
     *
     * @param Request $request
     *
     * @return string
     * @throws MissingShopDomainException
     */
    public static function getFromRequest(Request $request): string
    {
        // check the request input for the shop
        if ($request->has('shop')) {
            return self::fromNative($request->input('shop'))->toNative();
        }

        if ($request->has('shopDomain')) {
            return self::fromNative($request->input('shopDomain'))->toNative();
        }

        if ($request->has('token')) {
            try {
                $token = new SessionToken($request->input('token'), $verifyToken = false);

                if ($shopDomain = $token->getShopDomain()) {
                    return $shopDomain->toNative();
                }
            } catch (AssertionFailedException $e) {
                // unable to decode the token
            }
        }

        // check the referrer for the shop
        $referrer = $request->header('referer');

        if (filled($referrer)) {
            $query = parse_url($referrer, PHP_URL_QUERY);
            $params = parseQueryString($query);

            if (isset($params['shop'])) {
                return self::fromNative($params['shop'])->toNative();
            }

            if (isset($params['shopDomain'])) {
                return self::fromNative($params['shopDomain'])->toNative();
            }

            if (isset($params['token'])) {
                try {
                    $token = new SessionToken($params['token'], $verifyToken = false);

                    if ($shopDomain = $token->getShopDomain()) {
                        return $shopDomain->toNative();
                    }
                } catch (AssertionFailedException $e) {
                    // unable to decode the token
                }
            }
        }

        // unable to determine the shop
        throw new MissingShopDomainException('Unable to get shop domain from request');
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
        $configEndDomain = getShopifyConfig('myshopify_domain');
        $domain = strtolower(preg_replace('/https?:\/\//i', '', trim($domain)));

        if (strpos($domain, $configEndDomain) === false && strpos($domain, '.') === false) {
            // No myshopify.com ($configEndDomain) in shop's name
            $domain .= ".{$configEndDomain}";
        }

        // Return the host after cleaned up
        return parse_url("https://{$domain}", PHP_URL_HOST);
    }
}
