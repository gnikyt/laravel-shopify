<?php

namespace Osiset\ShopifyApp\Test\Objects\Transfers;

use Exception;
use Osiset\ShopifyApp\Objects\Transfers\Charge;
use Osiset\ShopifyApp\Test\TestCase;

class ChargeTest extends TestCase
{
    /**
     * @var \Osiset\ShopifyApp\Objects\Transfers\Charge
     */
    protected $charge;

    public function setUp(): void
    {
        parent::setUp();

        $this->charge = new Charge();
    }

    public function testGetThrowsAnError(): void
    {
        $this->expectExceptionObject(new Exception(
            'Property doesNotExist does not exist on transfer class '.Charge::class, 0
        ));

        $_ = $this->charge->doesNotExist;
    }

    public function testSetThrowsAnError(): void
    {
        $this->expectExceptionObject(new Exception(
            'Setting property doesNotExist for transfer class '.Charge::class, 0
        ));

        $this->charge->doesNotExist = false;
    }

    public function testToArray(): void
    {
        $this->assertEquals([
            'shopId' => null,
            'planId' => null,
            'chargeReference' => null,
            'chargeType' => null,
            'chargeStatus' => null,
            'activatedOn' => null,
            'billingOn' => null,
            'trialEndsOn' => null,
            'planDetails' => null,
        ], $this->charge->toArray());
    }
}
