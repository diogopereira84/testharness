<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpressCheckout\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Magento\Checkout\Model\CartFactory;
use Fedex\ExpressCheckout\Helper\ExpressCheckout;

class UpdateQuotePayment extends Action
{
    /**
     * @var JsonFactory $jsonFactory
     */
    protected $jsonFactory;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var CartFactory $cartFactory
     */
    protected $cartFactory;

    /**
     * @var ExpressCheckout $expressCheckoutHelper
     */
    protected $expressCheckoutHelper;

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param LoggerInterface $logger
     * @param CartFactory $cartFactory
     * @param ExpressCheckout $expressCheckoutHelper
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        LoggerInterface $logger,
        CartFactory $cartFactory,
        ExpressCheckout $expressCheckoutHelper
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
        $this->cartFactory = $cartFactory;
        $this->expressCheckoutHelper = $expressCheckoutHelper;
    }

    /**
     * Update quote
     *
     * @return string
     */
    public function execute()
    {
        $creditCard = $this->getRequest()->getPost('creditCard');
        $paymentMethod = $this->getRequest()->getPost('paymentMethod');
        $profileAddress = $this->getRequest()->getPost('profileAddress');
        $quote = $this->cartFactory->create()->getQuote();
        try {
            $this->expressCheckoutHelper->setPaymentInformation($creditCard, $paymentMethod, $profileAddress, $quote);
            $quote->save();
            $response = ["success" => true];
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
            'Update payment data in quote : ' . $e->getMessage());
            $response = ["success" => false];
        }
        $result = $this->jsonFactory->create();
        $result->setData($response);
        
        return $result;
    }
}
