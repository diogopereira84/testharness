<?php

namespace Fedex\CatalogMvp\Observer;

use Magento\Framework\Event\ObserverInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Psr\Log\LoggerInterface;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ProductEntitySaveAfterObserver implements ObserverInterface
{
    public function __construct(
        protected CatalogMvp $catalogMvpHelper,
        protected LoggerInterface $logger,
        protected CatalogDocumentRefranceApi $catalogdocumentrefapi,
        protected ToggleConfig $toggleConfig
    )
    {
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->catalogMvpHelper->isMvpCtcAdminEnable()) {
            $product = $observer->getProduct();
            $this->catalogdocumentrefapi->extendDocLifeApiSyncCall($product); 
        }
        return $this;
    }
}
