<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\SDE\Observer;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\Login\Helper\Login;
use Magento\Framework\Serialize\SerializerInterface;

class CartMerge implements ObserverInterface
{
    /**
     * CartMerge constructor
     *
     * @param LoggerInterface $logger
     * @param QuoteFactory $quoteFactory
     * @param StoreManagerInterface $storeManager
     * @param Login $login
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected QuoteFactory $quoteFactory,
        protected StoreManagerInterface $storeManager,
        protected Login $login,
        private SerializerInterface $serializer
    )
    {
    }

    /**
     * Execute observer
     * Remove cart merge when both quote are from different store
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $source = $observer->getEvent()->getSource();
            if (is_object($source)) {
                // Using Quote Repository we are not getting correct store id
                // so we used Quote Factory
                $guestCart = $this->quoteFactory->create()->load($source->getId());

                // Log cart items.(Once this toggle removed no need of If block)
                if ($this->login->isLoggingToggleEnable()) {
                    $allItems = $source->getAllVisibleItems();
                    foreach ($allItems as $item) {
                        $additionalOption = $item->getOptionByCode('info_buyRequest');
                        $additionalOptions = $additionalOption->getValue();
                        $decodedData = (array)$this->serializer->unserialize($additionalOptions);
                        $this->logger->info(__METHOD__ . ':' . __LINE__ . "Cart Quote Item." . $item->getProductId() . var_export($decodedData, true));
                    }
                }

                $companyId = $this->login->getCompanyId();
                if ($companyId || $this->storeManager->getStore()->getStoreId() != $guestCart->getStoreId()) {
                    $allItems = $source->getAllVisibleItems();
                    foreach ($allItems as $item) {
                        $itemId = $item->getItemId();
                        $source->removeItem($itemId)->save();
                    }
                }
            }

            return $this;
        } catch (Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ':' . $e->getMessage());
        }
    }
}
