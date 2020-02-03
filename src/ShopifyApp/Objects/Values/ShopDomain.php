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
     */
    public function __construct(string $domain)
    {
        parent::__construct($domain);

        $this->string = ShopifyApp::sanitizeShopDomain($domain);
    }
}
