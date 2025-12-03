<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\PlaceOrder;

use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\OrderFactory;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Psr\Log\LoggerInterface;

class SubmitOrder
{
    /**
     * @param RequestData $requestData
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param OrderFactory $orderFactory
     * @param SubmitOrderBuilder $submitOrderBuilder
     * @param FuseBidViewModel $fuseBidViewModel
     * @param LoggerInterface $logger
     */
    public function __construct(
        private RequestData $requestData,
        private CartIntegrationRepositoryInterface $cartIntegrationRepository,
        private OrderFactory $orderFactory,
        private SubmitOrderBuilder $submitOrderBuilder,
        private FuseBidViewModel $fuseBidViewModel,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * @param $quote
     * @param $note
     * @return array|int[]|null
     * @throws \Exception
     */
    public function execute($quote, $note): ?array
    {
        $requestData = $this->requestData->build($quote, $note);

        try {
            /** @var CartIntegrationInterface $cartIntegration */
            $cartIntegration = $this->cartIntegrationRepository->getByQuoteId($quote->getId());

            if ($cartIntegration->getRetryTransactionApi()) {
                $order = $this->orderFactory->create()->loadByIncrementId($quote->getReservedOrderId());
                $response = $this->submitOrderBuilder->instoreBuildRetryTransaction($order, $quote, $requestData);
                $response["rateQuoteResponse"]["transactionId"] = $cartIntegration->getFjmpRateQuoteId();
                return $response;
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ .
                'Error in Fetching Quote Integration: ' . $e->getMessage()
            );
        }

        $quoteObj = null;
        if ($this->fuseBidViewModel->isFuseBidToggleEnabled() && $quote->getIsBid()) {
            $quoteObj = $quote;
        }

        return $this->submitOrderBuilder->build(
            $requestData,
            isset($requestData->pickupData),
            false,
            $quoteObj
        );
    }
}
