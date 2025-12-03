<?php
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Page\Config as PageConfig;

class AddBodyClassObserver implements ObserverInterface
{
    public function __construct(
        private PageConfig $pageConfig
    ) {
    }


    /**
     * Add html body class
     *
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $this->pageConfig->addBodyClass('sequence-numbers');
        return $this;
    }

}