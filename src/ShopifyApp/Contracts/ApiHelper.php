<?php

namespace Osiset\ShopifyApp\Contracts;

use stdClass;
use Osiset\BasicShopifyAPI;
use GuzzleHttp\Exception\RequestException;
use Osiset\ShopifyApp\Objects\Enums\AuthMode;
use Osiset\ShopifyApp\Objects\Enums\ChargeType;
use Osiset\ShopifyApp\Objects\Transfers\PlanDetails;
use Osiset\ShopifyApp\Objects\Values\ChargeReference;
use Osiset\ShopifyApp\Objects\Transfers\UsageChargeDetails;
use Osiset\ShopifyApp\Objects\Transfers\ApiSession as ApiSessionTransfer;

/**
 * Reprecents the API helper.
 */
interface ApiHelper
{
    /**
     * Create an API instance (without a context to a shop).
     *
     * @param ApiSessionTransfer $session The shop API session details.
     *
     * @return self
     */
    public function make(ApiSessionTransfer $session = null);

    /**
     * Set an existing API instance.
     *
     * @param BasicShopifyAPI $api The API instance.
     *
     * @return self
     */
    public function setApi(BasicShopifyAPI $api);

    /**
     * Get the existing instance.
     *
     * @return BasicShopifyAPI
     */
    public function getApi(): BasicShopifyAPI;

    /**
     * Build the authentication URL to Shopify.
     *
     * @param AuthMode $mode   The mode of authentication (offline or per-user).
     * @param string   $scopes The scopes for the authentication, comma-separated.
     *
     * @return string
     */
    public function buildAuthUrl(AuthMode $mode, string $scopes): string;

    /**
     * Determines if the request HMAC is verified.
     *
     * @param array $request The request parameters.
     *
     * @return bool
     */
    public function verifyRequest(array $request): bool;

    /**
     * Finish the process by getting the access details from the code.
     *
     * @param string $code The code from the request.
     *
     * @return object
     */
    public function getAccessData(string $code);

    /**
     * Get the script tags for the shop.
     *
     * @param array $params The params to set to the request.
     *
     * @throws RequestException
     *
     * @return array
     */
    public function getScriptTags(array $params = []): array;

    /**
     * Create a script tag for the shop.
     *
     * @param array $payload The data for the script tag creation.
     *
     * @throws RequestException
     *
     * @return stdClass
     */
    public function createScriptTag(array $payload): stdClass;

    /**
     * Get the charge record.
     *
     * @param ChargeType      $chargeType The type of charge (plural).
     * @param ChargeReference $chargeRef  The charge ID.
     *
     * @throws RequestException
     *
     * @return stdClass
     */
    public function getCharge(ChargeType $chargeType, ChargeReference $chargeRef): stdClass;

    /**
     * Activate a charge.
     *
     * @param ChargeType      $chargeType The type of charge (plural).
     * @param ChargeReference $chargeRef  The charge ID.
     *
     * @throws RequestExcpetion
     *
     * @return stdClass
     */
    public function activateCharge(ChargeType $chargeType, ChargeReference $chargeRef): stdClass;

    /**
     * Create a charge.
     *
     * @param ChargeType  $chargeType The type of charge (plural).
     * @param PlanDetails $payload    The data for the charge creation.
     *
     * @return stdClass
     */
    public function createCharge(ChargeType $chargeType, PlanDetails $payload): stdClass;

    /**
     * Get webhooks for the shop.
     *
     * @param array $params The params to set to the request.
     *
     * @throws RequestException
     *
     * @return array
     */
    public function getWebhooks(array $params = []): array;

    /**
     * Create a webhook.
     *
     * @param array $payload The data for the webhook creation.
     *
     * @return stdClass
     */
    public function createWebhook(array $payload): stdClass;

    /**
     * Delete a webhook.
     *
     * @param int $webhookId The webhook ID to delete.
     *
     * @return void
     */
    public function deleteWebhook(int $webhookId): stdClass;

    /**
     * Creates a usage charge for a recurring charge.
     *
     * @param UsageChargeDetails $payload The data for the usage charge creation.
     *
     * @return object
     */
    public function createUsageCharge(UsageChargeDetails $payload): stdClass;
}
