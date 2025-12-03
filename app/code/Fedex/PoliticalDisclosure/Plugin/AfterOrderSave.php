<?php
declare(strict_types=1);

namespace Fedex\PoliticalDisclosure\Plugin;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\PoliticalDisclosure\Api\OrderDisclosureRepositoryInterface;
use Fedex\PoliticalDisclosure\Model\OrderDisclosureFactory;
use Fedex\PoliticalDisclosure\Model\Config\PoliticalDisclosureConfig;
use Psr\Log\LoggerInterface;

class AfterOrderSave
{
    public function __construct(
        private readonly OrderDisclosureRepositoryInterface $repository,
        private readonly OrderDisclosureFactory             $factory,
        private readonly CartRepositoryInterface            $quoteRepository,
        private readonly PoliticalDisclosureConfig          $pdConfig,
        private readonly LoggerInterface                    $logger
    ) {}

    public function afterSave(
        OrderRepositoryInterface $subject,
        OrderInterface $order
    ): OrderInterface {
        try {

            if (empty($this->pdConfig->getEnabledStates())) {
                return $order;
            }

            $quoteId = (int)$order->getQuoteId();
            $orderId = (int)$order->getEntityId();

            if (!$quoteId || !$orderId) {
                return $order;
            }

            $model = $this->repository->getByQuoteId($quoteId);
            if (!$model) {
                return $order;
            }

            $model->setData('order_id', $orderId);
            $this->repository->save($model);

        } catch (\Throwable $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ .
                '[PD] Failed updating order_id for quote_id=' . $quoteId . ': ' . $e->getMessage());
        }

        return $order;
    }
}
