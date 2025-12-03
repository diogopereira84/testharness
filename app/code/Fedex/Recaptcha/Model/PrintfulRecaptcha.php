<?php
/**
 * @category Fedex
 * @package  Fedex_Recaptcha
 * @copyright   Copyright (c) 2025 Fedex
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Recaptcha\Model;

use Fedex\Recaptcha\Logger\RecaptchaLogger;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Store\Model\ScopeInterface;

class PrintfulRecaptcha
{
    const XML_PATH_RECAPTCHA_PRINTFUL_ENABLED = 'recaptcha_frontend/printful/enabled';
    const XML_PATH_RECAPTCHA_PRINTFUL_SCORE_THRESHOLD = 'recaptcha_frontend/printful/score_threshold';
    const XML_PATH_RECAPTCHA_PRINTFUL_LINE_QTY = 'recaptcha_frontend/printful/line_qty';
    const PRINTFUL_STORE_NAME = 'Printful';

    public function __construct(
        protected RecaptchaLogger         $recaptchaLogger,
        protected ScopeConfigInterface    $scopeConfig,
        protected SessionManagerInterface $sessionManagerInterface,
        protected CheckoutSession         $checkoutSession,
        protected RequestInterface        $requestInterface,
        protected AddressInterfaceFactory $addressFactory,
        protected RegionFactory           $regionFactory
    ) {}

    /**
     * Check if the current quote is eligible for Printful transaction block.
     *
     * This method verifies if the Printful Recaptcha Transaction Block is enabled
     * and if there is a quote in the checkout session. It then checks if the shipping and billing addresses
     * doesn't match. It then checks if all visible items in the quote belong to the
     * Printful store and match the specified line quantity.
     *
     * @return bool Returns true if the quote is eligible for Printful transaction block, false otherwise.
     */
    public function checkIfQuoteIsEligibleForPrintfulTransactionBlock(): bool
    {
        $quote = $this->checkoutSession->getQuote();
        if (!$this->isPrintfulRecaptchaTransactionBlockEnabled() || !$this->checkoutSession->hasQuote()) {
            return false;
        }

        $lineQty = $this->isPrintfulRecaptchaTransactionBlockLineQty();

        $this->generateBillingAddressFromSubmitOrderToCompare($quote);
        if ($this->compareQuoteAddresses($quote)) {
            return false;
        }

        foreach ($quote->getAllVisibleItems() as $item) {
            if ($item->getMiraklShopName() !== self::PRINTFUL_STORE_NAME || $item->getQty() > $lineQty) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate the billing address from the request data and set it to the quote.
     * @param $quote
     * @return void
     */
    public function generateBillingAddressFromSubmitOrderToCompare(&$quote)
    {
        $requestData = json_decode((string) $this->requestInterface->getPost('data'));
        $paymentData = $requestData->paymentData ?? null;
        $paymentData = is_object($paymentData) ? $paymentData : json_decode((string)$paymentData);
        if ($paymentData instanceof \stdClass && property_exists($paymentData, 'billingAddress')) {
            $quote->setBillingAddress($this->buildBillingAddress($paymentData, $quote));
        }
    }

    /**
     * Compare the shipping and billing addresses of the quote.
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return bool
     */
    public function compareQuoteAddresses(\Magento\Quote\Model\Quote $quote): bool
    {
        $shippingAddress = $quote->getShippingAddress();
        $billingAddress = $quote->getBillingAddress();

        $shippingAddressPostcode = $this->clearAddressZipCode($shippingAddress);
        $billingAddressPostcode = $this->clearAddressZipCode($billingAddress);

        return $shippingAddress->getRegionId() === $billingAddress->getRegionId()
            && strtolower($shippingAddress->getCity() ?? '') == strtolower($billingAddress->getCity() ?? '')
            && strtolower($shippingAddress->getStreetFull() ?? '') === strtolower($shippingAddress->getStreetFull() ?? '')
            && $shippingAddressPostcode === $billingAddressPostcode;
    }

    /**
     * Check if Printful Recaptcha Transaction Block is enabled.
     *
     * @return bool
     */
    public function isPrintfulRecaptchaTransactionBlockEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_RECAPTCHA_PRINTFUL_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get the line quantity for Printful Recaptcha Transaction Block.
     *
     * @return int
     */
    public function isPrintfulRecaptchaTransactionBlockLineQty(): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_RECAPTCHA_PRINTFUL_LINE_QTY,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get the threshold for Printful Recaptcha Transaction Block.
     *
     * @return float
     */
    public function isPrintfulRecaptchaTransactionBlockThreshold(): float
    {
        return (float)$this->scopeConfig->getValue(
            self::XML_PATH_RECAPTCHA_PRINTFUL_SCORE_THRESHOLD,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Build the billing address from the payment data.
     *
     * @param \stdClass $paymentData
     * @param $quote
     * @return AddressInterface
     */
    public function buildBillingAddress(\stdClass $paymentData, $quote): AddressInterface
    {
        $billingData = [];
        if (property_exists($paymentData, "billingAddress")) {
            if (isset($paymentData->billingAddress->regionCode)) {
                $billingRegion = $this->regionFactory->create();
                $billingRegion->loadByCode(
                    $paymentData->billingAddress->regionCode,
                    $quote->getBillingAddress()->getCountryId()
                );
                $billingData['region'] = $billingRegion->getName();
                $billingData['region_id'] = $billingRegion->getRegionId();
            }

            if (isset($paymentData->billingAddress->address)) {
                $street[] = $paymentData->billingAddress->address;
                if (isset($paymentData->billingAddress->addressTwo)) {
                    $street[] = $paymentData->billingAddress->addressTwo;
                }
                $billingData['street'] = $street;
            }

            if (isset($paymentData->billingAddress->city)) {
                $billingData['city'] = $paymentData->billingAddress->city;
            }

            if (isset($paymentData->billingAddress->postcode)) {
                $billingData['postcode'] = $paymentData->billingAddress->postcode;
            }
        }
        $address = $this->addressFactory->create();
        $address->setData($billingData);
        return $address;
    }

    public function clearAddressZipCode($address)
    {
        $zipCode = $address->getPostcode();
        if ($zipCode && str_contains($zipCode, '-')) {
            $zipCode = explode('-', $zipCode)[0] ?? (int)$zipCode;
        }
        return $zipCode;
    }
}
