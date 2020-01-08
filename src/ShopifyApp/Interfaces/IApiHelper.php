<?php

namespace OhMyBrew\ShopifyApp\Services;

use OhMyBrew\BasicShopifyAPI;
use GuzzleHttp\Exception\RequestException;
use OhMyBrew\ShopifyApp\DTO\PlanDetailsDTO;

/**
 * Reprecents the API helper.
 */
interface IApiHelper
{
    /**
     * Set the API instance.
     *
     * @param BasicShopifyAPI $api The API instance.
     *
     * @return self
     */
    public function setInstance(BasicShopifyAPI $api): self;

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
     * @param string $chargeType The type of charge (plural).
     * @param int    $chargeId   The charge ID.
     *
     * @return object|RequestException
     */
    public function getCharge(string $chargeType, int $chargeId): object;

    /**
     * Activate a charge.
     *
     * @param string $chargeType The type of charge (plural).
     * @param int    $chargeId   The charge ID.
     *
     * @return object|RequestException
     */
    public function activateCharge(string $chargeType, int $chargeId): object;

    /**
     * Create a charge.
     *
     * @param string         $chargeType The type of charge (plural).
     * @param PlanDetailsDTO $payload    The data for the charge creation.
     *
     * @return object
     */
    public function createCharge(string $chargeType, PlanDetailsDTO $payload): object;

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
}
