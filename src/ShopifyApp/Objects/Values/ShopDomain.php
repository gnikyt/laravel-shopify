<?php

namespace Osiset\ShopifyApp\Objects\Values;

use Funeralzone\ValueObjects\Scalars\StringTrait;
use Osiset\ShopifyApp\Contracts\Objects\Values\ShopDomain as ShopDomainValue;
use Osiset\ShopifyApp\Util;

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
