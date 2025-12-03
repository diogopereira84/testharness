<?php

namespace Fedex\CartGraphQl\Test\Unit\Model\RecipientsBuilder\CollectRates;

use Fedex\CartGraphQl\Model\RateQuote\RecipientsBuilder\AbstractData;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Override;

class ConcreteData extends AbstractData
{
    #[Override]
    public function getIdentifierKey(): string
    {
        return 'test_key';
    }

    #[Override]
    public function proceed(
        string $referenceId,
        CartIntegrationInterface $integration,
        array $productAssociations,
        ?string $requestedPickupLocalTime = null,
        ?string $requestedDeliveryLocalTime = null,
        ?string $shippingEstimatedDeliveryLocalTime = null,
        ?string $holdUntilDate = null
    ): array {
        return ['proceeded' => true];
    }
}
