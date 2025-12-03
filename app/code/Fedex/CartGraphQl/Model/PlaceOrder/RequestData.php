<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\PlaceOrder;

use Exception;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\CartGraphQl\Api\PlaceOrderRequestInterface;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Magento\Quote\Api\Data\CartInterface;

class RequestData implements PlaceOrderRequestInterface
{
    /**
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param CartDataHelper $cartHelper
     * @param array $deliveryData
     */
    public function __construct(
        private CartIntegrationRepositoryInterface $cartIntegrationRepository,
        private CartDataHelper $cartHelper,
        private array $deliveryData = []
    ) {
    }

    /**
     * @param CartInterface $quote
     * @param array|null $notes
     * @return object
     * @throws Exception
     */
    public function build(
        CartInterface $quote,
        ?array $notes = null
    ): object {
        try {
            $data = [];
            $integration = $this->cartIntegrationRepository->getByQuoteId($quote->getId());

            foreach ($this->deliveryData as $deliveryData) {
                $data = array_merge($data, $deliveryData->getData($integration) ?? []);
            }

            return (object) array_merge($this->getRequestData($quote, $notes), $data);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param CartInterface $quote
     * @param array|null $notes
     * @return array
     */
    private function getRequestData(CartInterface $quote, ?array $notes = null): array
    {
        return [
            "useSiteCreditCard" => false,
            "paymentData" => json_encode([
                "paymentMethod" => self::PAYMENT_METHOD,
                "fedexAccountNumber" => $quote->getFedexAccountNumber() ? $this->cartHelper->decryptData($quote->getFedexAccountNumber()) : null,
                "lteIdentifier" => $quote->getLteIdentifier()
            ]),
            "encCCData" => null,
            "notes" => $notes
        ];
    }
}
