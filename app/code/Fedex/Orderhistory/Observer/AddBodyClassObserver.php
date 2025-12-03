<?php
declare(strict_types=1);

namespace Fedex\Orderhistory\Observer;

use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Page\Config as PageConfig;

class AddBodyClassObserver implements ObserverInterface
{

    public function __construct(
        protected PageConfig   $pageConfig,
        private DeliveryHelper $deliveryHelper
    )
    {
    }//end __construct()


    /**
     * Add html body class
     *
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $isCommercial = $this->deliveryHelper->isCommercialCustomer();

        if ($isCommercial) {
            $this->pageConfig->addBodyClass('marketplace-history');
        }

        return $this;

    }//end execute()

}//end class
