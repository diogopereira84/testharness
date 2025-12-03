<?php
declare(strict_types=1);
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Delivery\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\CartFactory;
use Psr\Log\LoggerInterface;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\FXOPricing\Model\FXORateQuote;

/**
 * Class to call RateQuote api to set requested pickup time
 * with RateQuote save method call
 */
class RateQuoteApi extends \Magento\Framework\App\Action\Action
{
    /**
     * RateApi Constructor
     *
     * @param Context $context
     * @param CartFactory $cartFactory
     * @param LoggerInterface $logger
     * @param CartDataHelper $cartDataHelper
     * @param FXORateQuote $fxoRateQuote
     */
    public function __construct(
        protected Context $context,
        protected CartFactory $cartFactory,
        protected LoggerInterface $logger,
        protected CartDataHelper $cartDataHelper,
        protected FXORateQuote $fxoRateQuote,
    ) {
        parent::__construct($context);
    }

    /**
     * Execute Constroller action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $requestedPickupDateTime = $this->getRequest()->getParam('requestedPickupDateTime');
        $fedExAccountNumber = null;
        try {
            $quote = $this->cartFactory->create()->getQuote();
            if ($requestedPickupDateTime) {
                $quote->setData('requestedPickupDateTime', $requestedPickupDateTime);
                $locationId = $this->getRequest()->getParam('locationId') ?? null;
                $fedExAccountNumber = $this->getFedexAccountNumber($quote);
                // Set Pickup data in request
                $this->setPickupData($quote, $locationId, $fedExAccountNumber);
                // Call RateQuote API.
                $this->fxoRateQuote->getFXORateQuote($quote);
            }
        } catch (\Exception $error) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                ' No data being returned from rate api: ' . $error->getMessage());
        }
    }

    /**
     * setPickupData
     */
    public function setPickupData($quote, $locationId, $fedExAccountNumber) : void
    {
        $arrRequestPickupData = [
            'locationId'         => $locationId,
            'fedExAccountNumber' => $fedExAccountNumber,
        ];
        $quote->setCustomerPickupLocationData($arrRequestPickupData);
        $quote->setIsFromPickup(true);
    }

    /**
     * Get FedEx Account from Quote
     *
     * @return String \Fedex\Cart\Helper\Data
     */
    public function getFedexAccountNumber($quote)
    {
        if ($quote->getData('fedex_account_number')) {
            return $this->cartDataHelper->decryptData($quote->getData('fedex_account_number'));
        }
    }
}
