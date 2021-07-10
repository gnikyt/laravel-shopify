<?php

namespace Osiset\ShopifyApp\Objects\Values;

use Assert\Assert;
use Assert\AssertionFailedException;
use Funeralzone\ValueObjects\Scalars\StringTrait;
use Illuminate\Support\Carbon;
use Osiset\ShopifyApp\Contracts\Objects\Values\SessionToken as SessionTokenValue;
use Osiset\ShopifyApp\Contracts\Objects\Values\ShopDomain as ShopDomainValue;
use Osiset\ShopifyApp\Util;

/**
 * Value object for a session token (JWT).
 */
final class SessionToken implements SessionTokenValue
{
    use StringTrait;

    /**
     * The regex for the format of the JWT.
     *
     * @var string
     */
    public const TOKEN_FORMAT = '/^eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9\.[A-Za-z0-9\-\_=]+\.[A-Za-z0-9\-\_\=]*$/';

    /**
     * Message for malformed token.
     *
     * @var string
     */
    public const EXCEPTION_MALFORMED = 'Session token is malformed.';

    /**
     * Message for invalid token.
     *
     * @var string
     */
    public const EXCEPTION_INVALID = 'Session token is invalid.';

    /**
     * Message for expired token.
     *
     * @var string
     */
    public const EXCEPTION_EXPIRED = 'Session token has expired.';

    /**
     * Token parts.
     *
     * @var array
     */
    protected $parts;

    /**
     * Issuer.
     *
     * @var string
     */
    protected $iss;

    /**
     * Destination identity.
     *
     * @var string
     */
    protected $dest;

    /**
     * Audience.
     *
     * @var string
     */
    protected $aud;

    /**
     * Subject.
     *
     * @var string
     */
    protected $sub;

    /**
     * Expiration.
     *
     * @var Carbon
     */
    protected $exp;

    /**
     * Not before.
     *
     * @var Carbon
     */
    protected $nbf;

    /**
     * Issued at.
     *
     * @var Carbon
     */
    protected $iat;

    /**
     * JWT identity.
     *
     * @var string
     */
    protected $jti;

    /**
     * Session identity.
     *
     * @var SessionId
     */
    protected $sid;

    /**
     * The shop domain parsed from destination.
     *
     * @var ShopDomainValue
     */
    protected $shopDomain;

    /**
     * Contructor.
     *
     * @param string $token The JWT.
     * @param bool $verifyToken Should the token be verified? Use false to only decode the token.
     *
     * @throws AssertionFailedException
     */
    public function __construct(string $token, bool $verifyToken = true)
    {
        // Confirm token formatting and decode the token
        $this->string = $token;
        $this->decodeToken();

        if ($verifyToken) {
            // Confirm token signature, validity, and expiration
            $this->verifySignature();
            $this->verifyValidity();
            $this->verifyExpiration();
        }
    }

    /**
     * Decode and validate the formatting of the token.
     *
     * @throws AssertionFailedException If token is malformed.
     *
     * @return void
     */
    protected function decodeToken(): void
    {
        // Confirm token formatting
        Assert::that($this->string)->regex(self::TOKEN_FORMAT, self::EXCEPTION_MALFORMED);

        // Decode the token
        $this->parts = explode('.', $this->string);
        $body = json_decode(Util::base64UrlDecode($this->parts[1]), true);

        // Confirm token is not malformed
        Assert::thatAll([
            $body['iss'],
            $body['dest'],
            $body['aud'],
            $body['sub'],
            $body['exp'],
            $body['nbf'],
            $body['iat'],
            $body['jti'],
            $body['sid'],
        ])->notNull(self::EXCEPTION_MALFORMED);

        // Format the values
        $this->iss = $body['iss'];
        $this->dest = $body['dest'];
        $this->aud = $body['aud'];
        $this->sub = $body['dest'];
        $this->jti = $body['dest'];
        $this->sid = SessionId::fromNative($body['sid']);
        $this->exp = new Carbon($body['exp']);
        $this->nbf = new Carbon($body['nbf']);
        $this->iat = new Carbon($body['iat']);

        // Parse the shop domain from the destination
        $host = parse_url($body['dest'], PHP_URL_HOST);
        $this->shopDomain = NullableShopDomain::fromNative($host);
    }

    /**
     * Get the shop domain.
     *
     * @return ShopDomainValue
     */
    public function getShopDomain(): ShopDomainValue
    {
        return $this->shopDomain;
    }

    /**
     * Get the session ID.
     *
     * @return SessionId
     */
    public function getSessionId(): SessionId
    {
        return $this->sid;
    }

    /**
     * Get the expiration time of the token.
     *
     * @return Carbon
     */
    public function getExpiration(): Carbon
    {
        return $this->exp;
    }

    /**
     * Checks the validity of the signature sent with the token.
     *
     * @throws AssertionFailedException If signature does not match.
     *
     * @return void
     */
    protected function verifySignature(): void
    {
        // Get the token without the signature present
        $partsCopy = $this->parts;
        $signature = Hmac::fromNative(array_pop($partsCopy));
        $tokenWithoutSignature = implode('.', $partsCopy);

        // Create a local HMAC
        $secret = Util::getShopifyConfig('api_secret', $this->shopDomain);
        $hmac = Util::createHmac(['data' => $tokenWithoutSignature, 'raw' => true], $secret);
        $encodedHmac = Hmac::fromNative(Util::base64UrlEncode($hmac->toNative()));

        Assert::that($signature->isSame($encodedHmac))->true();
    }

    /**
     * Checks the token to ensure the issuer and audience matches.
     *
     * @throws AssertionFailedException If invalid token.
     *
     * @return void
     */
    protected function verifyValidity(): void
    {
        Assert::that($this->iss)->contains($this->dest, self::EXCEPTION_INVALID);
        Assert::that($this->aud)->eq(Util::getShopifyConfig('api_key'), self::EXCEPTION_INVALID);
    }

    /**
     * Checks the token to ensure its not expired.
     *
     * @throws AssertionFailedException If token is expired.
     *
     * @return void
     */
    protected function verifyExpiration(): void
    {
        $now = Carbon::now();
        Assert::thatAll([
            $now->greaterThan($this->exp),
            $now->lessThan($this->nbf),
            $now->lessThan($this->iat),
        ])->false(self::EXCEPTION_EXPIRED);
    }
}
