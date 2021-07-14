<?php

namespace Osiset\ShopifyApp\Objects\Values;

use Funeralzone\ValueObjects\CompositeTrait;
use Funeralzone\ValueObjects\ValueObject;
use Illuminate\Support\Arr;
use Osiset\ShopifyApp\Contracts\Objects\Values\AccessToken as AccessTokenValue;
use Osiset\ShopifyApp\Contracts\Objects\Values\SessionId as SessionIdValue;
use Osiset\ShopifyApp\Contracts\Objects\Values\SessionToken as SessionTokenValue;

/**
 * Used to inject current session data into the user's model.
 * TODO: Possibly move this to a composite VO?
 */
final class SessionContext implements ValueObject
{
    use CompositeTrait;

    /**
     * The session token.
     *
     * @var SessionTokenValue
     */
    private $sessionToken;

    /**
     * The session ID.
     *
     * @var SessionIdValue
     */
    private $sessionId;

    /**
     * The offline access token.
     *
     * @var AccessTokenValue
     */
    private $accessToken;

    /**
     * Constructor.
     *
     * @param SessionTokenValue $sessionToken
     * @param SessionIdValue    $sessionId
     * @param AccessTokenValue  $accessToken
     *
     * @return void
     */
    public function __construct(
        SessionTokenValue $sessionToken,
        SessionIdValue $sessionId,
        AccessTokenValue $accessToken
    ) {
        $this->sessionToken = $sessionToken;
        $this->sessionId = $sessionId;
        $this->accessToken = $accessToken;
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
     * Get the access token.
     *
     * @return AccessTokenValue
     */
    public function getAccessToken(): AccessTokenValue
    {
        return $this->accessToken;
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
     * {@inheritDoc}
     */
    public static function fromNative($native)
    {
        return new static(
            NullableSessionToken::fromNative(Arr::get($native, 'session_token')),
            NullableSessionId::fromNative(Arr::get($native, 'session_id')),
            NullableAccessToken::fromNative(Arr::get($native, 'access_token'))
        );
    }

    /**
     * Confirm session is valid.
     * TODO: Add per-user support.
     *
     * @param SessionContext|null $previousContext The last session context (if available).
     *
     * @return bool
     */
    public function isValid(?self $previousContext = null): bool
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
            $domainCheck = $previousToken->getShopDomain()->isSame($currentToken->getShopDomain());

            // Compare the session IDs
            if (! $previousContext->getSessionId()->isNull() && ! $this->getSessionId()->isNull()) {
                $sidCheck = $previousContext->getSessionId()->isSame($this->getSessionId());
            }
        }

        return $tokenCheck && $sidCheck && $domainCheck;
    }
}
