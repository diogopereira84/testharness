<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\RateQuote\RecipientsBuilder;

use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\CartGraphQl\Api\Data\RecipientsBuilderDataInterface;
use Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery\Data\PickupData as ResolverPickupData;

class PickupData extends AbstractData implements RecipientsBuilderDataInterface
{
    public const IDENTIFIER_KEY = ResolverPickupData::IDENTIFIER_KEY;

    /**
     * @return string
     */
    public function getIdentifierKey(): string
    {
        return self::IDENTIFIER_KEY;
    }

    /**
     * Proceed getting delivery data to for RateQuote API
     *
     * @param string $referenceId
     * @param CartIntegrationInterface $integration
     * @param array $productAssociations
     * @param string|null $requestedPickupLocalTime
     * @param string|null $requestedDeliveryLocalTime
     * @param string|null $shippingEstimatedDeliveryLocalTime
     * @param string|null $holdUntilDate
     * @return array[]
     */
    public function proceed(
        string $referenceId,
        CartIntegrationInterface $integration,
        array $productAssociations,
        ?string $requestedPickupLocalTime = null,
        ?string $requestedDeliveryLocalTime = null,
        ?string $shippingEstimatedDeliveryLocalTime = null,
        ?string $holdUntilDate = null
    ): array {
        $arrRecipients = [
            'arrRecipients' => [
                0 => [
                    'contact' => null,
                    'reference' => $referenceId,
                    'pickUpDelivery' => [
                        'location' => [
                            'id' => $integration->getStoreId(),
                        ],
                        'requestedPickupLocalTime' => $requestedPickupLocalTime,
                    ],
                    'productAssociations' => $productAssociations,
                ],
            ]
        ];

        if ($this->instoreConfig->isDeliveryDatesFieldsEnabled()) {
            $arrRecipients['arrRecipients'][0]['requestedDeliveryLocalTime'] = $requestedDeliveryLocalTime;
            $arrRecipients['arrRecipients'][0]['pickUpDelivery']['holdUntilDate'] = $holdUntilDate;
        }

        return $arrRecipients;
    }
}
