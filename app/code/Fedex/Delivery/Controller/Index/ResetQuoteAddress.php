<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Delivery\Controller\Index;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceProduct\Helper\Quote;
use Magento\Framework\App\Action\Context;
use Fedex\FXOPricing\Model\FXORateQuote;
use Magento\Checkout\Model\CartFactory;
use Fedex\FXOPricing\Helper\FXORate;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * ResetQuoteAddress Controller
 */
class ResetQuoteAddress extends \Magento\Framework\App\Action\Action
{
    public const XML_PATH_D203990_TOGGLE = 'tiger_d203990';

    /**
     * @param Context $context
     * @param FXORateQuote $fxoRateQuote
     * @param CartFactory $cartFactory
     * @param FXORate $fxoRate
     * @param LoggerInterface $logger
     * @param ToggleConfig $toggleConfig
     * @param Quote $quoteHelper
     */
    public function __construct(
        Context $context,
        protected FXORateQuote $fxoRateQuote,
        protected CartFactory $cartFactory,
        protected FXORate $fxoRate,
        protected LoggerInterface $logger,
        protected ToggleConfig $toggleConfig,
        protected Quote $quoteHelper
    ) {
        parent::__construct($context);
    }

    /**
     * Reset Quote Address and call RateQuote API
     *
     * @return mixed
     */
    public function execute()
    {
        $quote = $this->cartFactory->create()->getQuote();
        $this->resetShippingAddress($quote);
        $this->resetBillingAddress($quote);

        try {
            $quote->save();
        } catch (\Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ . ':Quote save error ',
                ['exception' => $e->getMessage()]
            );
        }

        $isEproCustomer = $this->fxoRate->isEproCustomer();

        if ($isEproCustomer) {
            $apiResponse = $this->fxoRate->getFXORate($quote);
            $apiResponseKey = 'rate';
            $apiResponseDetailKey = 'rateDetails';
        } else {
            $apiResponse = $this->fxoRateQuote->getFXORateQuote($quote);
            $apiResponseKey = 'rateQuote';
            $apiResponseDetailKey = 'rateQuoteDetails';
        }

        $amountResponse = $this->handleApiResponse($apiResponse, $apiResponseKey, $apiResponseDetailKey);

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($amountResponse);

        return $resultJson;
    }

    /**
     * Handle Rate and RateQuote API response
     *
     * @param array $apiResponse
     * @param string $apiResponseKey
     * @param string $apiResponseDetailKey
     * @return array|json
     */
    public function handleApiResponse($apiResponse, $apiResponseKey, $apiResponseDetailKey)
    {
        if (isset($apiResponse['output'][$apiResponseKey])
            && isset($apiResponse['output'][$apiResponseKey][$apiResponseDetailKey])
        ) {
            $shippingAmount = 0;

            if (empty($apiResponse['output'][$apiResponseKey][$apiResponseDetailKey][0]['deliveryLines'])) {
                $shippingAmount = $this->fxoRateQuote->getDeliveryRatePrice($apiResponse);
            }

            $amountResponse = [
                'netAmount' => $apiResponse['output'][$apiResponseKey][$apiResponseDetailKey][0]['netAmount'],
                'taxAmount' => $apiResponse['output'][$apiResponseKey][$apiResponseDetailKey][0]['taxAmount'],
                'shippingAmount' => $shippingAmount
            ];

            return $amountResponse;
        } else {
            return $apiResponse;
        }
    }

    /**
     * Reset Quote Shipping Address
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return void
     */
    public function resetShippingAddress($quote)
    {
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setFirstname(null);
        $shippingAddress->setLastname(null);
        $shippingAddress->setStreet(null);
        $shippingAddress->setCity(null);
        $shippingAddress->setRegion(null);
        $shippingAddress->setRegionId(null);
        $shippingAddress->setPostcode(null);
        $shippingAddress->setTelephone(null);
        $shippingAddress->setShippingMethod(null);
        $shippingAddress->setShippingDescription(null);
        if($this->isD194518Enabled()) {
            $shippingAddress->setPickupAddress(null);
        }
        $quote->setShippingAddress($shippingAddress);
        if ($this->toggleConfig->getToggleConfigValue(self::XML_PATH_D203990_TOGGLE)
            && $this->quoteHelper->isMiraklQuote($quote)) {
            $quote->setProductionLocationId(null);
        }
    }

    /**
     * Reset Quote Billing Address
     *
     * @param object $quote
     * @return void
     */
    public function resetBillingAddress($quote)
    {
        $billingAddress = $quote->getBillingAddress();
        $billingAddress->setFirstname(null);
        $billingAddress->setLastname(null);
        $billingAddress->setStreet(null);
        $billingAddress->setCity(null);
        $billingAddress->setRegion(null);
        $billingAddress->setRegionId(null);
        $billingAddress->setPostcode(null);
        $billingAddress->setTelephone(null);
        $billingAddress->setShippingMethod(null);
        $billingAddress->setShippingDescription(null);
        if($this->isD194518Enabled()) {
            $billingAddress->setPickupAddress(null);
        }
        $quote->setShippingAddress($billingAddress);
    }

    /**
     * @return bool|int
     */
    private function isD194518Enabled()
    {
        return $this->toggleConfig->getToggleConfigValue('tiger_d194518');
    }
}
