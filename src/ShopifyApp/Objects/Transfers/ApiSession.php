<?php

namespace OhMyBrew\ShopifyApp\Objects\Transfers;

use OhMyBrew\ShopifyApp\Contracts\Objects\Values\AccessToken as AccessTokenValue;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\ShopDomain as ShopDomainValue;

/**
 * Reprecents details for API session used by API helper.
 */
class ApiSession extends AbstractTransfer
{
    /**
     * Constructor.
     *
     * @param ShopDomainValue  $domain The shop's domain.
     * @param AccessTokenValue $token  The access token.
     *
     * @return self
     */
    public function __construct(ShopDomainValue $domain, AccessTokenValue $token)
    {
        $this->data['domain'] = $domain;
        $this->data['token'] = $token;
    }
}
