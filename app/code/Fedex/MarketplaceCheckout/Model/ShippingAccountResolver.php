<?php
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model;

use Fedex\MarketplaceCheckout\Model\Config;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceHelper;
use Magento\Quote\Api\Data\AddressInterface;
class ShippingAccountResolver
{
    public function __construct(
        private RequestParser     $requestParser,
        private Config            $config,
        private MarketplaceHelper $marketplaceHelper
    ) {
    }

    /**
     * Determines if the shipping destination is a residence.
     *
     * @param string $request The request data to check.
     * @return bool True if the destination is a residence, false otherwise.
     */
    public function isShippingToResidence(string $request): bool
    {
        $requestData = $this->requestParser->parseJson($request);

        if (!isset($requestData['address']['custom_attributes'])) {
            return false;
        }

        return $this->hasResidenceShippingAttribute(
            $requestData['address']['custom_attributes']
        );
    }

    /**
     * Retrieves the FedEx account number from the request.
     *
     * @param mixed $request The request object or data.
     * @return string The FedEx account number.
     */
    public function getFedExAccountNumber(mixed $request): string
    {
        $requestData = $this->requestParser->parseJson($request->getContent());
        return $requestData['fedEx_account_number'] ?? '';
    }

    /**
     * Determines if the request is for a pickup.
     *
     * @param mixed $request The request object or data.
     * @return bool True if the request is for a pickup, false otherwise.
     */
    public function isPickup(mixed $request): bool
    {
        $requestData = $this->requestParser->parseJson($request->getContent());
        return $requestData['isPickup'] ?? false;
    }

    /**
     * Validates the provided address.
     *
     * @param AddressInterface $address The address to validate.
     * @return bool True if the address is valid, false otherwise.
     */
    public function isAddressValid(AddressInterface $address): bool
    {
        return $this->validateAddress($address);
    }

    /**
     * Retrieves the shipping account number from the provided shop shipping information.
     *
     * @param array $shopShippingInfo The shop's shipping information.
     * @param mixed $request The request object or data.
     * @return string The shipping account number.
     */
    public function resolveShippingAccountNumber(array $shopShippingInfo, $request): string
    {
        $marketPlaceShippingAccountNumber = $this->getFedExAccountNumber($request);

        if ($this->shouldUseMarketplaceAccount($shopShippingInfo, $marketPlaceShippingAccountNumber)) {
            return $marketPlaceShippingAccountNumber;
        }

        return $shopShippingInfo['shipping_account_number'];
    }

    private function shouldUseMarketplaceAccount(array $shopShippingInfo, string $marketPlaceShippingAccountNumber): bool
    {
        return $this->marketplaceHelper->isCustomerShippingAccount3PEnabled()
            && $this->marketplaceHelper->isVendorSpecificCustomerShippingAccountEnabled()
            && $shopShippingInfo['customer_shipping_account_enabled']
            && !empty($marketPlaceShippingAccountNumber);
    }

    private function validateAddress(AddressInterface $address): bool
    {
        return $address->getPostcode()
            && $address->getCity()
            && $address->getRegionCode()
            && $address->getStreet();
    }

    private function hasResidenceShippingAttribute(array $attributes): bool
    {
        foreach ($attributes as $attribute) {
            if ($attribute['attribute_code'] !== 'residence_shipping') {
                continue;
            }

            $value = $attribute['value'];
            return $this->config->isIncorrectShippingTotalsToggleEnabled()
                ? ($value === true || $value === 1)
                : ($value === true);
        }

        return false;
    }
}
