<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\ExpressCheckout\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Psr\Log\LoggerInterface;
use Magento\Directory\Model\Region;
use Magento\Quote\Model\Quote\PaymentFactory;

/**
 * Express Checkout Helper
 */
class ExpressCheckout extends AbstractHelper
{
    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var Region $region
     */
    protected $region;

    /**
     * @var PaymentFactory $paymentFactory
     */
    protected $paymentFactory;

    /**
     * Express Checkout Constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param LoggerInterface $logger
     * @param Region $region
     * @param PaymentFactory $paymentFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        LoggerInterface $logger,
        Region $region,
        PaymentFactory $paymentFactory
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->region = $region;
        $this->paymentFactory = $paymentFactory;
    }

    /**
     * Set payment information in quote
     *
     * @param array $creditCard
     * @param string $paymentMethod
     * @param array $profileAddress
     * @param object $quote
     *
     * @return void
     */
    public function setPaymentInformation($creditCard, $paymentMethod, $profileAddress, $quote)
    {
        $payment = $this->paymentFactory->create();
        $payment->setMethod($paymentMethod);
        $quote->setPayment($payment);
        $quote->getPayment()->importData(['method' => $paymentMethod]);
        if ($creditCard !== null) {
            $billingAddress = $this->preparePaymentBillingAddress($creditCard, $profileAddress);
            $quote->getBillingAddress()->addData($billingAddress["addressInformation"]["billing_address"]);
        }
    }

    /**
     * Set payment billing information in quote
     *
     * @param array $creditCard
     * @param array $profileAddress
     *
     * @return array
     */
    public function preparePaymentBillingAddress($creditCard, $profileAddress)
    {
        $creditCard = json_decode($creditCard, true);

        $regionId = $this->getRegionId($creditCard["billingAddress"]);
        return [
            'addressInformation' => [
                'billing_address' => [
                    'region' => $creditCard["billingAddress"]["stateOrProvinceCode"] ?? null,
                    'region_id' => $regionId,
                    'region_code' => $creditCard["billingAddress"]["stateOrProvinceCode"] ?? null,
                    'country_id' => $creditCard["billingAddress"]["countryCode"] ?? null,
                    'street' => [
                        0 => $creditCard["billingAddress"]["streetLines"][0] ?? null,
                    ],
                    'postcode' => $creditCard["billingAddress"]["postalCode"] ?? null,
                    'city' => $creditCard["billingAddress"]["city"] ?? null,
                    'firstname' => $profileAddress["firstName"] ?? null,
                    'lastname' => $profileAddress["lastName"] ?? null,
                    'email' => $profileAddress["email"] ?? null,
                    'telephone' => $profileAddress["phoneNumber"] ?? null,
                ]
            ]
        ];
    }

    /**
     * Set customer information in quote
     *
     * @param array $profileAddress
     * @param object $quote
     *
     * @return void
     */
    public function setCustomerInformation($profileAddress, $quote)
    {

        $customerFirstName = $profileAddress["firstName"] ?? null;
        $customerLastName = $profileAddress["lastName"] ?? null;
        $customerEmailId = $profileAddress["email"] ?? null;
        $customerPhoneNumber = $profileAddress["phoneNumber"] ?? null;

        $quote->setData("customer_firstname", $customerFirstName);
        $quote->setData("customer_lastname", $customerLastName);
        $quote->setData("customer_email", $customerEmailId);
        $quote->setData("customer_telephone", $customerPhoneNumber);
    }

    /**
     * Set shipping and billing address in quote
     *
     * @param object $quote
     * @param array $shippingData
     *
     * @return void
     */
    public function setShippingBillingAddress($quote, $shippingData)
    {
        $quote->getBillingAddress()->addData($shippingData["addressInformation"]["shipping_address"]);
        $quote->getShippingAddress()->addData($shippingData["addressInformation"]["billing_address"]);

        $shippingMethod = $shippingData["addressInformation"]["shipping_carrier_code"] . "_" .
        $shippingData["addressInformation"]["shipping_method_code"];

        $quote->getShippingAddress()->setShippingMethod($shippingMethod);
        $quote->getShippingAddress()->setShippingDescription($shippingData["addressInformation"]["method_title"]);
        $quote->getShippingAddress()->setCollectShippingRates(true);

        $quote->getBillingAddress()->setShippingMethod($shippingMethod);
        $quote->getBillingAddress()->setShippingDescription($shippingData["addressInformation"]["method_title"]);
        $quote->getBillingAddress()->setCollectShippingRates(true);
    }

    /**
     * Get Region Id from location address
     *
     * @param array $locationAddress
     *
     * @return string|int
     */
    public function getRegionId($locationAddress)
    {
        $regionCode = $locationAddress["stateOrProvinceCode"] ?? null;
        $countryCode = $locationAddress["countryCode"] ?? null;
        
        return $this->region->loadByCode($regionCode, $countryCode)->getId();
    }

    /**
     * Prepare shipping address data
     *
     * @param array $locationAddress
     * @param int $locationId
     * @param array $profileAddress
     *
     * @return array
     */
    public function prepareShippingData($locationAddress, $locationId, $profileAddress)
    {
        $regionId = $this->getRegionId($locationAddress);

        return [
            'addressInformation' => [
                'shipping_address' => [
                    'region' => $locationAddress["stateOrProvinceCode"] ?? null,
                    'region_id' => $regionId,
                    'region_code' => $locationAddress["stateOrProvinceCode"] ?? null,
                    'country_id' => $locationAddress["countryCode"] ?? null,
                    'street' => [
                        0 => $locationAddress["streetLines"][0] ?? null,
                    ],
                    'postcode' => $locationAddress["postalCode"] ?? null,
                    'city' => $locationAddress["city"] ?? null,
                    'firstname' => $profileAddress["firstName"] ?? null,
                    'lastname' => $profileAddress["lastName"] ?? null,
                    'email' => $profileAddress["email"] ?? null,
                    'telephone' => $profileAddress["phoneNumber"] ?? null,
                ],
                'billing_address' => [
                    'region' => $locationAddress["stateOrProvinceCode"] ?? null,
                    'region_id' => $regionId,
                    'region_code' => $locationAddress["stateOrProvinceCode"] ?? null,
                    'country_id' => $locationAddress["countryCode"] ?? null,
                    'street' => [
                        0 => $locationAddress["streetLines"][0] ?? null,
                    ],
                    'postcode' => $locationAddress["postalCode"] ?? null,
                    'city' => $locationAddress["city"] ?? null,
                    'firstname' => $profileAddress["firstName"] ?? null,
                    'lastname' => $profileAddress["lastName"] ?? null,
                    'email' => $profileAddress["email"] ?? null,
                    'telephone' => $profileAddress["phoneNumber"] ?? null,
                ],
                'shipping_carrier_code' => "fedexshipping",
                'shipping_method_code' => "PICKUP",
                'carrier_title' => "Fedex Store Pickup",
                'method_title' => $locationId,
                'amount' => 0,
                'price_excl_tax' => 0,
                'price_incl_tax' => 0,
            ],
        ];
    }
}
