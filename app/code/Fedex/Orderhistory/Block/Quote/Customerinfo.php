<?php

namespace Fedex\Orderhistory\Block\Quote;

use Fedex\Orderhistory\Helper\Data;
use Magento\Quote\Model\QuoteFactory;
use Magento\Framework\View\Element\Template\Context;

/**
 * Block for preparing Contact Detatils in quote view page.
 *
 * @api
 * @since 100.0.0
 */
class Customerinfo extends \Magento\Framework\View\Element\Template
{
    /**
     * Customerinfo constructor.
     *
     * @param Context $context
     * @param Data $orderHelper
     * @param QuoteFactory $quoteFactory
     */

    public function __construct(
        Context $context,
        public Data $orderHelper,
        public QuoteFactory $quoteFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Retrieve Customer Details for Contact Info on Quote View Page.
     * B-1112160 - View Quote Details.
     *
     */
    public function getCustomerDetails()
    {
        $quoteId = $this->getRequest()->getParam('quote_id');
        return $this->quoteFactory->create()->load($quoteId);
    }

    /**
     * @inheritDoc
     *
     */
    public function getQuote()
    {
        return $this->getCustomerDetails();
    }

    /**
     * @inheritDoc
     *
     */
    public function getFormattedPhone($telephone)
    {
        $telephone = substr_replace($telephone, '(', 0, 0);
        $telephone = substr_replace($telephone, ')', 4, 0);
        $telephone = substr_replace($telephone, ' ', 5, 0);
        $telephone = substr_replace($telephone, '-', 9, 0);
        return $telephone;
    }

    /**
     * @inheritDoc
     *
     */
    public function isEnhancementEnabledForPrint()
    {
        return $this->orderHelper->isEnhancementEnabledForPrint();
    }

    /**
     * @inheritDoc
     *
     */
    public function getDeliveryMethodName($shippingMethod, $shippingDescription)
    {
        $shippingDeliveryMethodName = '';
        if (!empty($shippingDescription) && substr_count($shippingDescription, '-') == 2) {
            $shippingDeliveryMethod = explode("-", $shippingDescription);
            $shippingDeliveryMethodName = trim($shippingDeliveryMethod[1]);
        } elseif (!empty($shippingDescription) && substr_count($shippingDescription, '-') == 1) {
            $shippingDeliveryMethod = explode("-", $shippingDescription);
            $shippingDeliveryMethodName = trim($shippingDeliveryMethod[0]);
        }

        return $shippingDeliveryMethodName;
    }

    /**
     * @inheritDoc
     *
     */
    public function getEstimatedShippingDelivery($shippingDescription)
    {
        $estimatedDelivery = '';
        if (!empty($shippingDescription)
        && substr_count($shippingDescription, '-') == 2
        && substr_count($shippingDescription, ',') == 2) {
            $shippingDeliveryDateTime = explode("-", $shippingDescription);
            $estimatedDelivery = trim($shippingDeliveryDateTime[2]);
        } elseif (!empty($shippingDescription)
        && substr_count($shippingDescription, '-') == 1
        && substr_count($shippingDescription, ',') == 2) {
            $shippingDeliveryDateTime = explode("-", $shippingDescription);
            $estimatedDelivery = trim($shippingDeliveryDateTime[1]);
        } elseif (!empty($shippingDescription)
        && substr_count($shippingDescription, '-') == 2
        && substr_count($shippingDescription, ',') == 1) {
            $shippingDeliveryDateTime = explode("-", $shippingDescription);
            $deliveryDateTime = explode(" ", trim($shippingDeliveryDateTime[2]));
            $estimatedDelivery = $deliveryDateTime[0].' '.$deliveryDateTime[1].' '.$deliveryDateTime[2].', '.
            $deliveryDateTime[3].$deliveryDateTime[4];
        } elseif (!empty($shippingDescription)
        && substr_count($shippingDescription, '-') == 1
        && substr_count($shippingDescription, ',') == 1) {
            $shippingDeliveryDateTime = explode("-", $shippingDescription);
            $deliveryDateTime = explode(" ", trim($shippingDeliveryDateTime[1]));
            $estimatedDelivery = $deliveryDateTime[0].' '.$deliveryDateTime[1].' '.$deliveryDateTime[2].', '.
            $deliveryDateTime[3].$deliveryDateTime[4];
        }

        return $estimatedDelivery;
    }
}
