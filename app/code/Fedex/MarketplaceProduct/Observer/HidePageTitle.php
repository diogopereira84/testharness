<?php

declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Observer;

use Fedex\MarketplaceProduct\Helper\Data as MiraklHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class HidePageTitle implements ObserverInterface
{
    public function __construct(
        private MiraklHelper $miraklHelper
    ) {
    }

    /**
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $fullActionName = $observer->getFullActionName();

        if ($fullActionName == 'catalog_product_view' && $this->miraklHelper->canMovePageTitleToNewLocation()) {
            $layout = $observer->getEvent()->getLayout();
            $pageTitleBlock = $layout->getBlock('page.main.title');
            if ($pageTitleBlock) {
                $pageTitleBlock->addData(['css_class' => 'hidden-m-l-xl']);
            }
        }
        return $this;
    }
}
