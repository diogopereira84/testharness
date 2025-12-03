<?php
/**
 * @category Fedex
 * @package  Fedex_SubmitOrderSidebar
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model;

use Magento\Directory\Model\RegionFactory;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use stdClass;

/**
 * Class BillingAddressBuilder
 *
 * Used to generate Billing Address from SubmitOrder controller
 * Related to D-60365
 */
class BillingAddressBuilder
{
    /**
     * @param AddressInterfaceFactory $addressFactory
     * @param RegionFactory $regionFactory
     */
    public function __construct(
        protected AddressInterfaceFactory $addressFactory,
        protected RegionFactory $regionFactory
    )
    {
    }

    /**
     * @param stdClass $paymentData
     * @param $quote
     * @return AddressInterface
     */
    public function build(stdClass $paymentData, $quote): AddressInterface
    {
        $billingData = [];
        if (property_exists($paymentData, "billingAddress")) {
            if (isset($paymentData->billingAddress->state)) {
                $billingRegion = $this->regionFactory->create();
                $billingRegion->loadByCode(
                    $paymentData->billingAddress->state,
                    $quote->getBillingAddress()->getCountryId()
                );
                $billingData['region'] = $billingRegion->getName();
                $billingData['region_id'] = $billingRegion->getRegionId();
            }

            if (isset($paymentData->billingAddress->address)) {
                $billingData['street'] = $paymentData->billingAddress->address;
                if (isset($paymentData->billingAddress->addressTwo)) {
                    $billingData['street'] = $paymentData->billingAddress->address . " "
                        . $paymentData->billingAddress->addressTwo;
                }
            }

            if (isset($paymentData->billingAddress->company)) {
                $billingData['company'] = $paymentData->billingAddress->company;
            }

            if (isset($paymentData->billingAddress->city)) {
                $billingData['city'] = $paymentData->billingAddress->city;
            }

            if (isset($paymentData->billingAddress->zip)) {
                $billingData['postcode'] = $paymentData->billingAddress->zip;
            }
        }
        $address = $this->addressFactory->create();
        $address->setData($billingData);
        return $address;
    }

    /**
     * Get Customer Details
     *
     * @param array $data
     * @return array
     */
    public function getCustomerDetails($data)
    {
        $commonDataVar = $data['rateQuoteRequest']['retailPrintOrder']['orderContact']['contact'];
        $fName = $commonDataVar['personName']['firstName'];
        $lName = $commonDataVar['personName']['lastName'];
        $email = $commonDataVar['emailDetail']['emailAddress'];
        $phNumber = $commonDataVar['phoneNumberDetails'][0]['phoneNumber']['number'];
        $companyName = $commonDataVar['company']['name'];
        $extension = $commonDataVar['phoneNumberDetails'][0]['phoneNumber']['extension'];

        return [
            'fName' => $fName,
            'lName' => $lName,
            'email' => $email,
            'phNumber' => $phNumber,
            'companyName' => $companyName,
            'extension' => $extension
        ];
    }

    /**
     * Get Updated Credit Card Detail
     *
     * @param string $response
     * @param int|string $ccToken
     * @param string $nameOnCard
     * @return array
     */
    public function getUpdatedCreditCardDetail($response, $ccToken, $nameOnCard)
    {
        if (isset($response->output->creditCard->creditCardToken)) {
            $ccToken = $response->output->creditCard->creditCardToken;
        }
        if (isset($response->output->creditCard->cardHolderName)) {
            $nameOnCard = $response->output->creditCard->cardHolderName;
        }

        return ['ccToken' => $ccToken, 'nameOnCard' => $nameOnCard];
    }
}
