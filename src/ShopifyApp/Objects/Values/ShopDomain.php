<?php

namespace OhMyBrew\ShopifyApp\Objects\Values;

use Funeralzone\ValueObjects\Scalars\StringTrait;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\ShopDomain as ShopDomainValue;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;

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
     * @return self
     */
    public function __construct(string $domain)
    {
        $this->string = ShopifyApp::sanitizeShopDomain($domain);
    }
}
