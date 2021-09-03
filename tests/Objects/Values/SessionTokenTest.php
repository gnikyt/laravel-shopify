<?php

namespace Osiset\ShopifyApp\Test\Objects\Values;

use Assert\AssertionFailedException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Osiset\ShopifyApp\Contracts\Objects\Values\SessionId as SessionIdValue;
use Osiset\ShopifyApp\Contracts\Objects\Values\ShopDomain as ShopDomainValue;
use Osiset\ShopifyApp\Objects\Values\SessionToken;
use Osiset\ShopifyApp\Test\TestCase;

class SessionTokenTest extends TestCase
{
    public function testShouldProcessForValidToken(): void
    {
        $token = $this->buildToken();
        $st = SessionToken::fromNative($token);

        $this->assertInstanceOf(ShopDomainValue::class, $st->getShopDomain());
        $this->assertTrue(Str::contains($this->tokenDefaults['dest'], $st->getShopDomain()->toNative()));

        $this->assertInstanceOf(SessionIdValue::class, $st->getSessionId());
        $this->assertSame($this->tokenDefaults['sid'], $st->getSessionId()->toNative());

        $this->assertInstanceOf(Carbon::class, $st->getExpiration());
        $this->assertSame($this->tokenDefaults['exp'], $st->getExpiration()->unix());
    }

    public function testShouldThrowExceptionForMalformedToken(): void
    {
        $this->expectException(AssertionFailedException::class);

        $token = $this->buildToken().'OOPS';
        SessionToken::fromNative($token);
    }

    public function testShouldThrowExceptionForInvalidToken(): void
    {
        $this->expectException(AssertionFailedException::class);

        $token = $this->buildToken(['iss' => 'someone-else.myshopify.com/admin']);
        SessionToken::fromNative($token);
    }

    public function testShouldThrowExceptionForExpiredToken(): void
    {
        $this->expectException(AssertionFailedException::class);

        $token = $this->buildToken(['exp' => Carbon::now()->subDay()]);
        SessionToken::fromNative($token);
    }
}
