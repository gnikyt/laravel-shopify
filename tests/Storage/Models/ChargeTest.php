<?php

namespace Osiset\ShopifyApp\Test\Storage\Models;

use Osiset\ShopifyApp\Contracts\ShopModel as IShopModel;
use Osiset\ShopifyApp\Objects\Enums\ChargeStatus;
use Osiset\ShopifyApp\Objects\Enums\ChargeType;
use Osiset\ShopifyApp\Objects\Values\ChargeId;
use Osiset\ShopifyApp\Objects\Values\ChargeReference;
use Osiset\ShopifyApp\Storage\Models\Charge;
use Osiset\ShopifyApp\Test\TestCase;

class ChargeTest extends TestCase
{
    public function testModel(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        // Create a charge
        $charge = factory(Charge::class)->states('type_recurring')->create([
            'user_id' => $shop->getId()->toNative(),
        ]);

        $this->assertInstanceOf(ChargeId::class, $charge->getId());
        $this->assertInstanceOf(ChargeReference::class, $charge->getReference());
        $this->assertInstanceOf(IShopModel::class, $charge->shop);
        $this->assertNull($charge->plan);
        $this->assertFalse($charge->isTest());
        $this->assertFalse($charge->isTrial());
        $this->assertTrue($charge->isType(ChargeType::RECURRING()));
        $this->assertTrue($charge->isStatus(ChargeStatus::ACCEPTED()));
        $this->assertFalse($charge->isActive());
        $this->assertTrue($charge->isAccepted());
        $this->assertFalse($charge->isDeclined());
        $this->assertFalse($charge->isCancelled());
        $this->assertFalse($charge->isOngoing());
        $this->assertEquals('recurring_application_charge', $charge->getTypeApiString());
        $this->assertEquals('recurring_application_charges', $charge->getTypeApiString(true));
    }
}
