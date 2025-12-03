<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Purchaseorder\Helper;

use Fedex\Company\Api\Data\ConfigInterface;
use Fedex\Company\Model\AdditionalDataFactory;
use Fedex\Ondemand\Api\Data\ConfigInterface as OndemandConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class PurchaseOrderHelper
{
    /**
     * Purchase Order Helper Constructor
     *
     * @param AdditionalDataFactory $additionalDataFactory
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param ConfigInterface $configInterface
     * @param OndemandConfigInterface $ondemandConfigInterface
     */
    public function __construct(
        protected AdditionalDataFactory $additionalDataFactory,
        protected LoggerInterface $logger,
        protected StoreManagerInterface $storeManager,
        protected ConfigInterface $configInterface,
        protected OndemandConfigInterface $ondemandConfigInterface
    )
    {
    }

    /**
     * Get Store Code using company Id
     *
     * @param int $companyId
     * @return String|null
     */
    public function getStoreCode($companyId)
    {
        $ondemandStoreId = $this->ondemandConfigInterface->getB2bDefaultStore();
        $store = $this->storeManager->getStore($ondemandStoreId);
        if ($store && $store->getCode()) {
           return $store->getCode();
        }
    }
}
