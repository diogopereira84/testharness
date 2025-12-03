<?php
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model;

class RequestParser
{
    public function parseJson(string $json): array
    {
        $decoded = json_decode($json ?: '{}', true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON provided: ' . json_last_error_msg());
        }

        return $decoded ?? [];
    }
}
