<?php

declare(strict_types=1);

namespace Fedex\Ondemand\Observer\Frontend;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\LiveSearch\Model\SharedCatalogSkip;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;

class MoveBreadcrumbs implements ObserverInterface
{
    public function __construct(
        protected CatalogMvp $catalogMvpHelper,
        private SharedCatalogSkip $sharedCatalogSkip,
        private ToggleConfig $toggleConfig
    )
    {
    }

    /**
    *
    * @param Observer $observer
    * @return $this
    */
    public function execute(Observer $observer)
    {
        $isMvpCustomerAdmin = $this->catalogMvpHelper->isMvpSharedCatalogEnable();
        $layout = $observer->getEvent()->getLayout();
        $isSharedCatalogPage = $this->catalogMvpHelper->isSharedCatalogPage();
        if ($isSharedCatalogPage && $this->sharedCatalogSkip->checkIsSharedCatalogPage()) {
            $layout->unsetElement('category.products.list');
        }
        if ($this->catalogMvpHelper->isCommercialCustomer()) {
            $layout->unsetElement('header.top.commercial.login');
        }

        if (!$isMvpCustomerAdmin) {
            return $this;
        }


        /** @var Action $controller */
        $fullActionName = $observer->getFullActionName();
        if ($fullActionName == 'catalog_category_view' || $fullActionName == 'selfreg_ajax_productlistajax') {

	    if ($isMvpCustomerAdmin) {
                $layout->unsetElement('category-sidebar');
            }

            //checked condition for Livesearch
            if(!$this->sharedCatalogSkip->getLivesearchProductListingEnable() &&
            $this->sharedCatalogSkip->checkIsSharedCatalogPage())
            {
                $layout->unsetElement('columns.top');
                $layout->unsetElement('category.description');
                $layout->setChild('main', 'breadcrumbs', 'breadcrumbs');
                $layout->reorderChild('main', 'breadcrumbs', 1);
            }
          //checked condition for Livesearch
        }

        if ($isMvpCustomerAdmin) {
            $layout->unsetElement('catalog.topnav');
        }
        return $this;
    }
}
