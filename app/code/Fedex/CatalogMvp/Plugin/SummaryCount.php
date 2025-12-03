<?php

namespace Fedex\CatalogMvp\Plugin;

use Magento\Checkout\CustomerData\Cart;
use Magento\Checkout\Helper\Data;
use Magento\Checkout\Model\Session;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class SummaryCount
{
    /**
     * @param Session $checkoutSession
     * @param CatalogMvp $catalogMvpHelper
     * @param Data $checkoutHelper
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected Session $checkoutSession,
        protected CatalogMvp $catalogMvpHelper,
        protected Data $checkoutHelper,
        protected ToggleConfig $toggleConfig
    )
    {
    }

    public function afterGetSectionData(Cart $subject, $result)
    {
        $quote = $this->checkoutSession->getQuote();
        if ($this->catalogMvpHelper->isMvpSharedCatalogEnable()) {
            $itemsCount = 0;
            if ($this->toggleConfig->getToggleConfigValue('explorers_d_191188_fix')) {
                $quoteItems = $quote->getAllVisibleItems();
                foreach ($quoteItems as $quoteItem) {
                    $itemsCount++;
                }
            } else {
                $itemsCount = $quote->getItemsCount();
            }

            $result['summary_count'] = (int) $itemsCount;
        }

        $grandTotal = $quote->getGrandTotal();
        $result['grandTotal'] = $this->checkoutHelper->formatPrice($grandTotal);

        return $result;
    }
}
