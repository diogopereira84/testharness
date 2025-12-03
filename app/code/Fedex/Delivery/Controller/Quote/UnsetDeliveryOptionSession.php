<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Delivery\Controller\Quote;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;

class UnsetDeliveryOptionSession implements ActionInterface
{
    /**
     * UnsetDeliveryOptionSession class constructor
     *
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param JsonFactory $jsonFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected Context $context,
        protected CheckoutSession $checkoutSession,
        protected JsonFactory $jsonFactory,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * Unset delivery options session
     *
     */
    public function execute()
    {
        $response = false;
        try {
            $this->checkoutSession->unsCustomShippingMethodCode();
            $this->checkoutSession->unsCustomShippingCarrierCode();
            $this->checkoutSession->unsCustomShippingTitle();
            $this->checkoutSession->unsCustomShippingPrice();
            $this->checkoutSession->unsDeliveryOptions();
            $response = true;
        } catch (\Exception $e) {
            $this->logger->error("Delivery options session has not been unset with error: " . $e->getMessage());
            $response = "Delivery options session has not been unset.";
        }
        $result = $this->jsonFactory->create();
        $result->setData($response);
        return $result;
    }
}
