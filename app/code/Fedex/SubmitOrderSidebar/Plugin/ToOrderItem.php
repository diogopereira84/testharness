<?php
namespace Fedex\SubmitOrderSidebar\Plugin;

use Magento\Quote\Model\Quote\Item\ToOrderItem as QuoteToOrderItem;
use Fedex\CatalogMvp\Helper\CatalogMvp;

class ToOrderItem
{
    /**
     * @param  CatalogMvp $catalogMvpHelper
     */
    public function __construct(
        protected CatalogMvp $catalogMvpHelper
    )
    {
    }

    /**
     * aroundConvert
     *
     * @param QuoteToOrderItem $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param array $data
     *
     * @return \Magento\Sales\Model\Order\Item
     */
    public function aroundConvert(
        QuoteToOrderItem $subject,
        \Closure $proceed,
        $item,
        $data = []
    ) {
        // Get Order Item
        $orderItem = $proceed($item, $data);
        if($this->catalogMvpHelper->customDocumentToggle()) {
            // Get Quote Item's additional Options
            $additionalOptions = $item->getOptionByCode('customize_fields');
            // Check if there is any additional options in Quote Item
            if ($additionalOptions) {
                if($additionalOptions->getValue()) {
                    // Get Order Item's other options
                    $options = $orderItem->getProductOptions();
                    // Set additional options to Order Item
                    $options['customize_fields'] = json_decode($additionalOptions->getValue());
                    $orderItem->setProductOptions($options);
                }
            }
        }
        return $orderItem;
    }
}