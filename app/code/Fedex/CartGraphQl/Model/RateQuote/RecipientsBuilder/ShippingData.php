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
use Fedex\CartGraphQl\Model\RateQuote\ShippingDelivery;
use Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery\Data\ShippingData as ResolverShippingData;

class ShippingData extends AbstractData implements RecipientsBuilderDataInterface
{
    public const IDENTIFIER_KEY = ResolverShippingData::IDENTIFIER_KEY;

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
        $arrShippingAddress = $this->jsonSerializer->unserialize($integration->getDeliveryData());

        if ($this->instoreConfig->isEnableServiceTypeForRAQ()) {
            if ($this->shippingDelivery->validateIfLocalDelivery($arrShippingAddress['shipping_method'])) {
                $deliveryType = ShippingDelivery::LOCAL_DELIVERY;
                $deliveryData = $this->shippingDelivery->setLocalDelivery($arrShippingAddress);
            } else {
                $deliveryType = ShippingDelivery::EXTERNAL_DELIVERY;
                $deliveryData = $this->shippingDelivery->setExternalDelivery(
                    $arrShippingAddress,
                    null,
                    $shippingEstimatedDeliveryLocalTime
                );
            }
        } else {
            $deliveryType = ShippingDelivery::LOCAL_DELIVERY;
            $deliveryData = $this->shippingDelivery->setLocalDelivery($arrShippingAddress);
            unset($deliveryData['originAddress']);
            unset($deliveryData['shipmentAccountType']);
        }

        if ($this->instoreConfig->isDeliveryDatesFieldsEnabled()) {
            $deliveryData['requestedDeliveryLocalTime'] = $requestedDeliveryLocalTime;
        }

        return [
            'arrRecipients' => [
                0 => [
                    'contact' => null,
                    'reference' => $referenceId,
                    $deliveryType => $deliveryData,
                    'productAssociations' => $productAssociations
                ]
            ]
        ];
    }
}
