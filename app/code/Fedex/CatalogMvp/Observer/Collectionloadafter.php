<?php

namespace Fedex\CatalogMvp\Observer;

use Magento\Framework\Event\ObserverInterface;
use Fedex\Delivery\Helper\Data;
use Fedex\CatalogMvp\Helper\CatalogMvp;

class Collectionloadafter implements ObserverInterface
{

    public function __construct(
        private Data $deliveryhelper,
        private CatalogMvp $catalogMvpHelper
    )
    {
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $date = date('Y-m-d H:i:s');
        if ($this->deliveryhelper->isCommercialCustomer() && $this->catalogMvpHelper->isMvpSharedCatalogEnable()
        && !$this->catalogMvpHelper->isSelfRegCustomerAdmin() && !$this->catalogMvpHelper->checkPrintCategory()) {
            $productCollection = $observer->getEvent()->getCollection();
            $productCollection->addAttributeToSelect('*')
                ->addAttributeToFilter(
                    [
                        ['attribute' => 'end_date_pod','null' => true],
                        ['attribute' => 'end_date_pod','gteq' => $date],
                    ]
                )->addAttributeToFilter(
                    [
                        ['attribute' => 'start_date_pod','null' => true],
                        ['attribute' => 'start_date_pod','lteq' => $date],
                    ]
                )->addAttributeToFilter(
                    [
                        ['attribute' => 'published','eq' => 1],
                    ]
                );
        }
        return $this;
    }
}
