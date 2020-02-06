<?php

namespace OhMyBrew\ShopifyApp\Contracts;

use GuzzleHttp\Exception\RequestException;
use OhMyBrew\BasicShopifyAPI;
use OhMyBrew\ShopifyApp\Objects\Transfers\PlanDetails;
use OhMyBrew\ShopifyApp\Objects\Transfers\UsageChargeDetails;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeId;

/**
 * Reprecents the API helper.
 */
interface ApiHelper
{
    /**
     * Create an API instance (without a context to a shop).
     *
     * @return self
     */
    public function make();

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
     * @param string $mode   The mode of authentication (offline or per-user).
     * @param string $scopes The scopes for the authentication, comma-separated.
     *
     * @return string
     */
    public function buildAuthUrl(string $mode, string $scopes): string;

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
     * @return array|RequestException
     */
    public function getScriptTags(array $params = []): array;

    /**
     * Create a script tag for the shop.
     *
     * @param array $payload The data for the script tag creation.
     *
     * @return object|RequestException
     */
    public function createScriptTag(array $payload): object;

    /**
     * Get the charge record.
     *
     * @param string   $chargeType The type of charge (plural).
     * @param ChargeId $chargeId   The charge ID.
     *
     * @return object|RequestException
     */
    public function getCharge(string $chargeType, ChargeId $chargeId): object;

    /**
     * Activate a charge.
     *
     * @param string   $chargeType The type of charge (plural).
     * @param ChargeId $chargeId   The charge ID.
     *
     * @return object|RequestException
     */
    public function activateCharge(string $chargeType, ChargeId $chargeId): object;

    /**
     * Create a charge.
     *
     * @param string      $chargeType The type of charge (plural).
     * @param PlanDetails $payload    The data for the charge creation.
     *
     * @return object
     */
    public function createCharge(string $chargeType, PlanDetails $payload): object;

    /**
     * Get webhooks for the shop.
     *
     * @param array $params The params to set to the request.
     *
     * @return object|RequestException
     */
    public function getWebhooks(array $params = []): array;

    /**
     * Create a webhook.
     *
     * @param array $payload The data for the webhook creation.
     *
     * @return object
     */
    public function createWebhook(array $payload): object;

    /**
     * Delete a webhook.
     *
     * @param int $webhookId The webhook ID to delete.
     *
     * @return void
     */
    public function deleteWebhook(int $webhookId): void;

    /**
     * Creates a usage charge for a recurring charge.
     *
     * @param UsageChargeDetails $payload The data for the usage charge creation.
     *
     * @return object
     */
    public function createUsageCharge(UsageChargeDetails $payload): object;
}
