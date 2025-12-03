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
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class SaveQuoteIntegrationRetryData
{
    /**
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected CartIntegrationRepositoryInterface $cartIntegrationRepository,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * @param $quoteId
     * @param $transactionId
     * @return void
     */
    public function execute($quoteId, $transactionId): void
    {
        try {
            /** @var CartIntegrationInterface $cartIntegration */
            $cartIntegration = $this->cartIntegrationRepository->getByQuoteId($quoteId);
            $cartIntegration->setRetryTransactionApi(true);
            $cartIntegration->setFjmpRateQuoteId($transactionId);
            $this->cartIntegrationRepository->save($cartIntegration);
        } catch (NoSuchEntityException $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ .
                'Error in Fetching Quote Integration: ' . $e->getMessage()
            );
            return;
        }
    }
}
