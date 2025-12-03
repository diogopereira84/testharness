<?php

namespace Fedex\CatalogMvp\Api;

/**
 * @codeCoverageIgnore
 */
interface WebhookInterface
{
    /**
     * Execute the webhook logic.
     *
     * @param string $responseData
     * @return string
     */
    public function addProductToRM($responseData);
}