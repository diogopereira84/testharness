<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\RateQuote;

use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Stdlib\DateTime;
use Psr\Log\LoggerInterface;

/**
 * Class ShippingDelivery
 *
 * Responsible for building delivery data structures (local and external)
 * used in shipping estimates and requests.
 */
class ShippingDelivery
{
    public const LOCAL_DELIVERY = 'shipmentDelivery';
    public const EXTERNAL_DELIVERY = 'externalDelivery';

    public function __construct(
        private readonly InstoreConfig $instoreConfig,
        private readonly CartIntegrationInterface $cartIntegration,
        private readonly CartIntegrationRepositoryInterface $cartIntegrationRepository,
        private readonly JsonSerializer $jsonSerializer,
        private readonly DateTime $dateTime,
        protected LoggerInterface $logger
    ) {}

    /**
     * Check if the given service type is configured as a local delivery option.
     *
     * @param string $serviceType
     * @return bool
     */
    public function validateIfLocalDelivery(string $serviceType): bool
    {
        return in_array($serviceType, (array) $this->instoreConfig->serviceTypeForRAQ(), true);
    }

    /**
     * Prepare data structure for local delivery.
     *
     * @param array|object $shippingAddress
     * @param string|null $shipperRegionCode
     * @return array<string, mixed>
     */
    public function setLocalDelivery(array|object $shippingAddress, ?string $shipperRegionCode = null): array
    {
        [$shipmentAccountType, $serviceType, $fedExAccountNumber, $estimatedShipDate] =
            $this->setDeliveryData($shippingAddress);

        $address = $this->getShipmentDeliveryAddress($shippingAddress, $shipperRegionCode);

        return [
            'address' => $address,
            'originAddress' => $address,
            'holdUntilDate' => $estimatedShipDate,
            'shipmentAccountType' => $shipmentAccountType,
            'serviceType' => $serviceType,
            'productionLocationId' => null,
            'fedExAccountNumber' => $fedExAccountNumber,
            'deliveryInstructions' => null
        ];
    }

    /**
     * Prepare data structure for external delivery.
     *
     * @param array|object $shippingAddress
     * @param string|null $shipperRegionCode
     * @param string|null $shippingEstimatedDeliveryLocalTime
     * @return array<string, mixed>
     */
    public function setExternalDelivery(
        array|object $shippingAddress,
        ?string $shipperRegionCode = null,
        ?string $shippingEstimatedDeliveryLocalTime = null
    ): array {
        [$shipmentAccountType, $serviceType, $fedExAccountNumber, $estimatedShipDate] =
            $this->setDeliveryData($shippingAddress);

        $address = $this->getShipmentDeliveryAddress($shippingAddress, $shipperRegionCode);

        return [
            'address' => $address,
            'originAddress' => $address,
            'holdUntilDate' => null,
            'shipmentAccountType' => $shipmentAccountType,
            'incenterDeliveryOrder' => true,
            'serviceType' => $serviceType,
            'productionLocationId' => null,
            'fedExAccountNumber' => $fedExAccountNumber,
            'deliveryInstructions' => null,
            'estimatedShipDates' => $this->getEstimatedShipDates($estimatedShipDate)
        ];
    }

    /**
     * Generate a normalized address array for a shipping location.
     *
     * @param array|object $shippingAddress
     * @param string|null $shipperRegionCode
     * @return array<string, string|array>
     */
    public function getShipmentDeliveryAddress(array|object $shippingAddress, ?string $shipperRegionCode = null): array
    {
        if (is_array($shippingAddress)) {
            $streetLines = array_filter([
                $shippingAddress['shipping_location_street1'] ?? null,
                $shippingAddress['shipping_location_street2'] ?? null,
                $shippingAddress['shipping_location_street3'] ?? null,
            ]);

            return [
                'streetLines' => array_values($streetLines),
                'city' => $shippingAddress['shipping_location_city'] ?? '',
                'stateOrProvinceCode' => $shippingAddress['shipping_location_state'] ?? '',
                'postalCode' => $shippingAddress['shipping_location_zipcode'] ?? '',
                'countryCode' => 'US',
                'addressClassification' => $shippingAddress['shipping_address_classification'] ?? '',
            ];
        }

        return [
            'streetLines' => $shippingAddress->getStreetAddress(),
            'city' => $shippingAddress->getCity(),
            'stateOrProvinceCode' => $shipperRegionCode ?? '',
            'postalCode' => $shippingAddress->getZipcode(),
            'countryCode' => 'US',
            'addressClassification' => $shippingAddress->getAddressClassification(),
        ];
    }

    /**
     * Extract delivery data from the shipping address (array or object).
     *
     * @param array|object $shippingAddress
     * @return array{string|null, string|null, string|null, string|null, string|null}
     */
    private function setDeliveryData(array|object $shippingAddress): array
    {
        if (is_array($shippingAddress)) {
            $shipmentAccountType = $shippingAddress['shipping_account_type'] ?? null;
            $serviceType = $shippingAddress['shipping_method'] ?? null;
            $fedExAccountNumber = $shippingAddress['shipping_account_number'] ?? null;

            $estimatedShipDate = $this->instoreConfig->isDeliveryDatesFieldsEnabled()
                ? $this->formatEstimatedDate($shippingAddress['shipping_estimated_delivery_local_time'] ?? null)
                : $shippingAddress['shipping_estimated_delivery_local_time'] ?? null;

        } else {
            $quoteId = (int) $shippingAddress->getQuote()->getId();

            try {
                $integration = $this->cartIntegrationRepository->getByQuoteId($quoteId);
                $integrationDeliveryData = $integration->getDeliveryData() ?? '{}';
            } catch (NoSuchEntityException $e) {
                $this->logger->error(
                    __METHOD__ . ':' . __LINE__ .
                    'Error in Fetching Quote Integration: ' . $e->getMessage()
                );
                $integrationDeliveryData = '{}';
            }

            $deliveryData = $this->jsonSerializer->unserialize($integrationDeliveryData);
            $shipmentAccountType = $deliveryData['shipping_account_type'] ?? null;
            $serviceType = $shippingAddress->getShipMethod();
            $fedExAccountNumber = $shippingAddress->getFedexShipAccountNumber();
            $estimatedShipDate = $this->formatEstimatedDate($deliveryData['shipping_estimated_delivery_local_time'] ?? null);
        }

        return [$shipmentAccountType, $serviceType, $fedExAccountNumber, $estimatedShipDate];
    }

    /**
     * Build estimated ship date range structure.
     *
     * @param string|null $estimatedShipDate
     * @return array<string, string|null>
     */
    private function getEstimatedShipDates(?string $estimatedShipDate): array
    {
        return [
            'minimumEstimatedShipDate' => $estimatedShipDate,
            'maximumEstimatedShipDate' => $estimatedShipDate
        ];
    }

    /**
     * Format estimated delivery date using Magento's DateTime utility.
     *
     * @param string|null $date
     * @return string|null
     */
    private function formatEstimatedDate(?string $date): ?string
    {
        return $date ? $this->dateTime->formatDate($date, false) : null;
    }
}
