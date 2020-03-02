<?php

namespace OhMyBrew\ShopifyApp\Test\Storage\Models;

use OhMyBrew\ShopifyApp\Test\TestCase;
use OhMyBrew\ShopifyApp\Storage\Models\Charge;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeId;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeType;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeReference;
use OhMyBrew\ShopifyApp\Contracts\ShopModel as IShopModel;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeStatus;

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
