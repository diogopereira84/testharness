<?php
/**
 * @category     Fedex
 * @package      Fedex_SubmitOrderSidebar
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model\RateQuoteApi;

use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;

/**
 * @deprecated in B-2014766
 * @see \Fedex\CartGraphQl\Model\RateQuote\RecipientsBuilder
 */
class InStoreRecipientsBuilder
{
    /**
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param InstoreConfig $instoreConfig
     */
    public function __construct(
        private CartIntegrationRepositoryInterface $cartIntegrationRepository,
        private InstoreConfig $instoreConfig
    )
    {
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
        try {
            $integration = $this->cartIntegrationRepository->getByQuoteId($cartId);
            if ($integration) {
                $arrRecipients = [
                    'arrRecipients' => [
                        0 => [
                            'contact' => null,
                            'reference' => $referenceId,
                            'pickUpDelivery' => [
                                'location' => [
                                    'id' => $integration->getStoreId(),
                                ],
                                'requestedPickupLocalTime' => $requestedPickupLocalTime
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
        } catch (NoSuchEntityException $e) {
            return null;
        }
        return null;
    }
}
