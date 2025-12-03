<?php
declare(strict_types=1);

namespace Fedex\Orderhistory\Observer;

use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Page\Config as PageConfig;

class AddBodyClassObserverSelfreg implements ObserverInterface
{
    private const TOGGLE_MARKETPLACE_COMMERCIAL = 'tiger_tk_410245';

    /**
     * @param PageConfig $pageConfig
     * @param ToggleConfig $toggleConfig
     * @param DeliveryHelper $deliveryHelper
     */
    public function __construct(
        protected PageConfig   $pageConfig,
        protected ToggleConfig $toggleConfig,
        private DeliveryHelper $deliveryHelper
    ) {
    }

    /**
     * Add html body class
     *
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $isToggleEnabled = $this->toggleConfig->getToggleConfigValue(self::TOGGLE_MARKETPLACE_COMMERCIAL);
        $isCommercial = $this->deliveryHelper->isCommercialCustomer();

        if ($isToggleEnabled && $isCommercial) {
            $this->pageConfig->addBodyClass('marketplace-selfreg');
        }

        return $this;

    }//end execute()

}//end class
