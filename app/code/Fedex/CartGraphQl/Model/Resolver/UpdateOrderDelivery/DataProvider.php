<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery;

use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\CartGraphQl\Exception\GraphQlParamNullException;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;

class DataProvider
{

    private const IS_ALTERNATE_CONTACT_ENABLED = 'tiger_b_2740163';

    /**
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     */
    public function __construct(
        private readonly CartIntegrationRepositoryInterface $cartIntegrationRepository,
        private ToggleConfig $toggleConfig
    ) {
    }

    /**
     * @param Quote $cart
     * @param array $rateQuoteResponse
     * @return array
     */
    public function getFormattedData(Quote $cart, array $rateQuoteResponse): array
    {
        $integration = $this->cartIntegrationRepository->getByQuoteId($cart->getId());
        return [
            'store_id' => $integration->getStoreId(),
            'location_id' => $integration->getLocationId(),
            'currency' => 'USD',
            'contact_information' => $this->getContactInformation($cart),
            'recipient_information' => $this->getRecipientInformation($cart),
            'deliveryLines' => $this->getDeliveryLines($cart, $rateQuoteResponse),
            'gtn' => $cart->getGtn()
        ];
    }

    /**
     * @param Quote $cart
     * @return array
     */
    private function getContactInformation(Quote $cart): array
    {
        $billingAddress = $cart->getBillingAddress();
        $alternateContactResponse = $this->getAlternateContact($cart, $billingAddress);

        return [
            'retailcustomerid' => $cart->getCustomerId(),
            'firstname' => $cart->getCustomerFirstname(),
            'lastname' => $cart->getCustomerLastname(),
            'email' => $cart->getCustomerEmail(),
            'telephone' => $billingAddress->getTelephone(),
            'ext' => $cart->getData('customer_PhoneNumber_ext'),
            'has_alternate_person' => $cart->getData('is_alternate'),
            'alternate_contact' => $alternateContactResponse
        ];
    }

    /**
     * @param Quote $cart
     * @return array
     */
    private function getRecipientInformation(Quote $cart): array
    {
        $shippingAddress = $cart->getShippingAddress();
        return [
            'shipping_firstname' => $shippingAddress->getFirstname(),
            'shipping_lastname' => $shippingAddress->getLastname(),
            'shipping_company' => $shippingAddress->getCompany(),
            'shipping_location_street' => $shippingAddress->getStreet(),
            'shipping_location_city' => $shippingAddress->getCity(),
            'shipping_location_state' => $shippingAddress->getRegionCode(),
            'shipping_location_zipcode' => $shippingAddress->getPostcode(),
            'shipping_location_country' => $shippingAddress->getCountry(),
            'shipping_phone_number' => $shippingAddress->getTelephone(),
            'shipping_phone_ext' => $shippingAddress->getExtNo(),
            'shipping_email' => $shippingAddress->getEmail(),
            'shipping_address_classification' => $shippingAddress->getAddressClassification()
        ];
    }

    /**
     * @return array[]
     * @throws GraphQlParamNullException
     */
    private function getDeliveryLines(Quote $cart, $rateQuoteResponse): array
    {
        $deliveryLines = $rateQuoteResponse ?? $rateQuoteResponse['output']['rateQuote']['rateQuoteDetails'][0]['deliveryLines'][0];
        $deliveryLinesArray = [
            [
                'recipientReference' => $deliveryLines['recipientReference'] ?? null,
                'linePrice' => $deliveryLines['deliveryLinePrice'] ?? null,
                'estimatedDeliveryLocalTime' => $deliveryLines['estimatedDeliveryLocalTime'] ?? null,
                'deliveryLinePrice' => $deliveryLines['deliveryLinePrice'] ?? null,
                'priceable' => $deliveryLines['priceable'] ?? null,
                'deliveryRetailPrice' => $deliveryLines['deliveryRetailPrice'] ?? null,
                'deliveryDiscountAmount' => $deliveryLines['deliveryDiscountAmount'] ?? null
            ]
        ];
        return $deliveryLinesArray;
    }

    /**
     * @param Quote $cart
     * @param Address $billingAddress
     * @return array|null
     */
    private function getAlternateContact(Quote $cart, Address $billingAddress): ?array
    {
        $isAlternateContact = $this->toggleConfig->getToggleConfigValue(self::IS_ALTERNATE_CONTACT_ENABLED);
        return $cart->getData('is_alternate') ?
            ["firstname" => $billingAddress->getFirstname(),
                "lastname" => $billingAddress->getLastname(),
                "email" => $billingAddress->getEmail(),
                "telephone" => $billingAddress->getTelephone(),
                "ext" => $isAlternateContact ? $billingAddress->getExtNo() : $cart->getData('customer_PhoneNumber_ext')
            ] : null;
    }
}
