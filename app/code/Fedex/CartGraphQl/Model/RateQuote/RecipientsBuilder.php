<?php
/**
 * @category     Fedex
 * @package      Fedex_SubmitOrderSidebar
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\RateQuote;

use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\CartGraphQl\Api\RecipientsBuilderInterface;
use Fedex\FXOPricing\Model\RateQuoteApi\InStoreRecipientsBuilder;
use Magento\Framework\Exception\NoSuchEntityException;

class RecipientsBuilder implements RecipientsBuilderInterface
{
    /**
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param InStoreRecipientsBuilder $inStoreRecipientsBuilder
     * @param array $deliveryData
     */
    public function __construct(
        private CartIntegrationRepositoryInterface $cartIntegrationRepository,
        private InStoreRecipientsBuilder $inStoreRecipientsBuilder,
        private array $deliveryData = []
    ) {
    }

    /**
     * @param string $referenceId
     * @param int|string $cartId
     * @param array $productAssociations
     * @param string|null $requestedPickupLocalTime
     * @param string|null $requestedDeliveryLocalTime
     * @param string|null $shippingEstimatedDeliveryLocalTime
     * @param string|null $holdUntilDate
     * @return array|null
     */
    public function build(
        string $referenceId,
        int|string $cartId,
        array $productAssociations,
        ?string $requestedPickupLocalTime = null,
        ?string $requestedDeliveryLocalTime = null,
        ?string $shippingEstimatedDeliveryLocalTime = null,
        ?string $holdUntilDate = null
    ): ?array {
        $data = null;

        try {
            $integration = $this->cartIntegrationRepository->getByQuoteId($cartId);

            if (!$integration->getDeliveryData()) {
                return $this->inStoreRecipientsBuilder->build(
                    $referenceId,
                    $cartId,
                    $productAssociations,
                    $requestedPickupLocalTime,
                    $requestedDeliveryLocalTime,
                    $shippingEstimatedDeliveryLocalTime,
                    $holdUntilDate
                );
            }

            foreach ($this->deliveryData as $deliveryData) {
                $data = $deliveryData->getData(
                    $referenceId,
                    $integration,
                    $productAssociations,
                    $requestedPickupLocalTime,
                    $requestedDeliveryLocalTime,
                    $shippingEstimatedDeliveryLocalTime,
                    $holdUntilDate
                );

                if (is_array($data)) {
                    break;
                }
            }
        } catch (NoSuchEntityException $e) {
        }

        return $data;
    }
}
