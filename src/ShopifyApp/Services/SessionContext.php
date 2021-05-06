<?php

namespace Osiset\ShopifyApp\Services;

use Osiset\ShopifyApp\Contracts\Objects\Values\AccessToken as AccessTokenValue;
use Osiset\ShopifyApp\Contracts\Objects\Values\SessionId as SessionIdValue;
use Osiset\ShopifyApp\Contracts\Objects\Values\SessionToken as SessionTokenValue;
use Osiset\ShopifyApp\Objects\Values\NullAccessToken;
use Osiset\ShopifyApp\Objects\Values\NullSessionId;
use Osiset\ShopifyApp\Objects\Values\NullSessionToken;
use Osiset\ShopifyApp\Objects\Values\SessionToken;

/**
 * Used to inject current session data into the user's model.
 * TODO: Possibily move this to a composite VO?
 */
class SessionContext
{
    /**
     * The session token.
     *
     * @var SessionTokenValue
     */
    protected $sessionToken;

    /**
     * The session ID.
     *
     * @var SessionIdValue
     */
    protected $sessionId;

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
        $this->sessionToken = NullSessionToken::fromNative(null);
        $this->sessionId = NullSessionId::fromNative(null);
        $this->accessToken = NullAccessToken::fromNative(null);
    }

    /**
     * Set the session token.
     *
     * @param SessionTokenValue $token
     *
     * @return void
     */
    public function setSessionToken(SessionTokenValue $token): void
    {
        $this->sessionToken = $token;
    }

    /**
     * Get the session token.
     *
     * @return SessionTokenValue
     */
    public function getSessionToken(): SessionTokenValue
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
     * Set the session ID.
     *
     * @param SessionIdValue $id
     *
     * @return void
     */
    public function setSessionId(SessionIdValue $id): void
    {
        $this->sessionId = $id;
    }

    /**
     * Get the session ID.
     *
     * @return SessionIdValue
     */
    public function getSessionId(): SessionIdValue
    {
        return $this->sessionId;
    }

    /**
     * Confirm session is valid.
     * TODO: Add per-user support.
     *
     * @param SessionContext|null $previousContext The last session context (if available).
     *
     * @return bool
     */
    public function isValid(?SessionContext $previousContext = null): bool
    {
        // Confirm access token and session token are good
        $tokenCheck = ! $this->getAccessToken()->isEmpty() && ! $this->getSessionToken()->isNull();

        // Compare data
        $sidCheck = true;
        $domainCheck = true;
        if ($previousContext !== null) {
            /** @var $previousToken SessionToken */
            $previousToken = $previousContext->getSessionToken();
            /** @var $currentToken SessionToken */
            $currentToken = $this->getSessionToken();

            // Compare the domains
            $domainCheck = $previousToken->getShopDomain()->isSame($currentToken);

            // Compare the session IDs
            if (! $previousContext->getSessionId()->isNull() && ! $this->getSessionId()->isNull()) {
                $sidCheck = $previousContext->getSessionId()->isSame($this->getSessionId());
            }
        }

        return $tokenCheck && $sidCheck && $domainCheck;
    }
}
