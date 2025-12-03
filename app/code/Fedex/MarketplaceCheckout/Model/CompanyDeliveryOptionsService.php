<?php
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model;

use Fedex\Cart\ViewModel\CheckoutConfig;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\MarketplaceCheckout\Model\Constants\ShippingMethod;

class CompanyDeliveryOptionsService
{
    public function __construct(
        private RequestParser  $requestParser,
        private Config         $config,
        private CheckoutConfig $checkoutConfig,
        private DeliveryHelper $deliveryHelper
    ) {
    }

    /**
     * Retrieves the allowed delivery methods for the specified shop.
     *
     * @param array $shop The shop data.
     * @return array The list of allowed delivery methods.
     */
    public function getAllowedDeliveryMethods(array $shop): array
    {
        if ($this->checkoutConfig->isSelfRegCustomer()
            && $this->config->isMarketplaceEnabledForCommercialSites()) {
            $deliveryMethods = $this->getCompanyAllowedShippingMethods();
        } else {
            $deliveryMethods = $this->requestParser->parseJson($shop['shop']['shipping_methods']);
        }

        return $deliveryMethods;
    }

    private function getCompanyAllowedShippingMethods(): array
    {
        $company = $this->deliveryHelper->getAssignedCompany($this->deliveryHelper->getCustomer());
        if (!$company || !$company->getAllowedDeliveryOptions()) {
            return [];
        }

        $deliveryOptions = (array) $this->requestParser->parseJson($company->getAllowedDeliveryOptions());

        return array_map(
            static fn ($method) => [
                'shipping_method_name' =>
                    (ShippingMethod::fromString((string)$method)?->value) ?? strtoupper((string)$method),
            ],
            $deliveryOptions
        );
    }
}
