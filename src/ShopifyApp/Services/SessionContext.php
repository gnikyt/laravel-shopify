<?php

namespace Osiset\ShopifyApp\Services;

use Osiset\ShopifyApp\Objects\Values\SessionToken;
use Osiset\ShopifyApp\Contracts\Objects\Values\AccessToken as AccessTokenValue;
use Osiset\ShopifyApp\Objects\Values\NullAccessToken;

/**
 * Used to inject current session data into the user's model.
 */
class SessionContext
{
    /**
     * The session token.
     *
     * @var SessionToken
     */
    protected $sessionToken;

    /**
     * The offline access token.
     *
     * @var AccessTokenValue
     */
    protected $accessToken;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $this->sessionToken = null;
        $this->accessToken = NullAccessToken::fromNative(null);
    }

    /**
     * Set the session token.
     *
     * @param SessionToken $token
     *
     * @return void
     */
    public function setSessionToken(SessionToken $token): void
    {
        $this->sessionToken = $token;
    }

    /**
     * Get the session token.
     *
     * @return SessionToken|null
     */
    public function getSessionToken(): ?SessionToken
    {
        return $this->sessionToken;
    }

    /**
     * Set the offline access token.
     *
     * @param AccessTokenValue $token
     *
     * @return void
     */
    public function setAccessToken(AccessTokenValue $token): void
    {
        $this->accessToken = $token;
    }

    /**
     * Get the access token.
     *
     * @return AccessTokenValue
     */
    public function getAccessToken(): AccessTokenValue
    {
        return $this->accessToken;
    }

    /**
     * Confirm session is valid.
     * TODO: Add per-user support.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return ! $this->getAccessToken()->isEmpty() && ! $this->getSessionToken()->isNull();
    }
}
