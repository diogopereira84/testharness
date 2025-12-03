<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model;

use Fedex\MarketplaceCheckout\Api\RatesAndTransitPricingInterface;
use Fedex\MarketplaceCheckout\Model\Constants\RequestConstants;
use Fedex\MarketplaceCheckout\Model\Constants\ShippingAddressKeys;
use Fedex\MarketplaceCheckout\Model\Constants\ShippingConstants;
use Fedex\MarketplaceCheckout\Model\Constants\UnitConstants;
use Fedex\MarketplaceCheckout\Model\DTO\RatesAndTransitRequestDTO;
use Fedex\MarketplaceRates\Helper\Data;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class RatesAndTransitPricing implements RatesAndTransitPricingInterface
{
    protected string $shipDate;
    protected array $shopData;
    protected array $shippingAddress;
    protected array $offerAddress;
    protected string $shipAccountNumber;
    protected float $weight;
    protected int $totalPackageCount;
    protected $request;

    public function __construct(
        private Curl                  $curlClient,
        private Json                  $jsonSerializer,
        private LoggerInterface       $logger,
        private Data                  $helper,
        private CollectionFactory     $collectionFactory,
        private Config $config
    ) {
    }

    /**
     * Sets parameters for the rates and transit pricing request.
     *
     * @param RatesAndTransitRequestDTO $data
     * @return void
     */
    public function setParams(RatesAndTransitRequestDTO $data): void
    {
        $this->request = $data->request;
        $this->shipDate = $data->shipDate;
        $this->shopData = $data->shopData;
        $this->shippingAddress = $data->shippingAddress;
        $this->offerAddress = $data->offerAddress;
        $this->shipAccountNumber = $data->shipAccountNumber;
        $this->weight = $data->weight;
        $this->totalPackageCount = $data->totalPackageCount;
    }

    /**
     * Retrieves shipping rates based on the provided request and parameters.
     *
     * @param RatesAndTransitRequestDTO $data
     * @return array
     */
    public function getRates(RatesAndTransitRequestDTO $data): array
    {
        try {
            $this->setParams($data);

            return $this->makeRequest();

        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ': ' . $e->getMessage());
        }

        return [];
    }

    /**
     * @return array
     */
    private function makeRequest(): array
    {
        $setupURL = $this->helper->getShippingRatesUrl();
        $apiToken = $this->helper->getFedexRatesToken();

        if (!$this->isValidApiConfiguration($setupURL, $apiToken)) {
            return [];
        }

        $headers = $this->getHeaders($apiToken);
        $jsonBody = $this->jsonSerializer->serialize($this->getRequestBody());
        $this->setCurlOptions($headers);

        $this->logRequest($jsonBody);
        $this->curlClient->post($setupURL, $jsonBody);

        return $this->handleApiResponse($this->curlClient->getBody(), $this->curlClient->getStatus());
    }

    private function isValidApiConfiguration(?string $setupURL, ?string $apiToken): bool
    {
        if (empty($setupURL) || empty($apiToken)) {
            $this->logger->info(__METHOD__ . ': Missing setup URL or API token');
            return false;
        }
        return true;
    }

    private function logRequest(string $jsonBody): void
    {
        $this->logger->info(__METHOD__ . ': Request Data => ' . $jsonBody);
    }

    private function handleApiResponse(string $response, int $statusCode): array
    {
        $this->logger->info(__METHOD__ . ': Response Data => ' . $response);

        if ($statusCode === RequestConstants::SUCCESS_RESPONSE_CODE) {
            return $this->processResponse($response);
        }

        return [];
    }

    /**
     * @param array $headers
     * @return void
     */
    private function setCurlOptions(array $headers): void
    {
        $this->curlClient->setOptions([
            CURLOPT_CUSTOMREQUEST => RequestConstants::POST_REQUEST,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_ENCODING => '',
        ]);
    }

    /**
     * @param array $offerAddress
     * @return array
     */
    private function getOptimalAddress(array $offerAddresses): array
    {
        $sums = [];
        $maxAddress = [];
        $maxValue = PHP_INT_MIN;

        foreach ($offerAddresses as $address) {
            $currentSum = $this->calculateAddressScore($address, $sums);

            if ($currentSum > $maxValue) {
                $maxValue = $currentSum;
                $maxAddress = $address;
            }
        }

        return $maxAddress;
    }

    private function calculateAddressScore(array $address, array &$sums): int
    {
        $score = 0;

        foreach ($address as $key => $value) {
            if (!in_array($key, [ShippingAddressKeys::CITY, ShippingAddressKeys::STATE_OR_PROVINCE, ShippingAddressKeys::POSTAL_CODE])) {
                $sums[$key] = ($sums[$key] ?? 0) + $value;
                $score += (int) $value;
            }
        }

        return $score;
    }

    /**
     * @param string $token
     * @return string[]
     */
    private function getHeaders(string $token): array
    {
        return [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "Authorization: Bearer " . $token,
        ];
    }

    /**
     * @return array
     */
    private function getRequestBody(): array
    {
        $isResidential = $this->isResidentialAddress($this->request);
        $offerAddress = $this->getOptimalAddress($this->offerAddress);
        $regionCode = $this->getRegionCode($this->shopData, $offerAddress);

        return $this->getRequestPayload($offerAddress, $regionCode, $isResidential);
    }

    private function getRequestPayload($offerAddress, $regionCode, $isResidential): array
    {
        return [
            "rateRequestControlParameters" => $this->getRateRequestControlParameters(),
            "requestedShipment" => $this->getRequestedShipment($offerAddress, $regionCode, $isResidential),
            "carrierCodes" => $this->getCarrierCodes(),
            "returnLocalizedDateTime" => true,
            "accountNumber" => ["value" => $this->shipAccountNumber],
        ];
    }

    private function getRateRequestControlParameters(): array
    {
        return [
            "rateSortOrder" => RequestConstants::RATE_SORT_ORDER,
            "returnTransitTimes" => true,
        ];
    }

    private function getRequestedShipment(array $offerAddress, string $regionCode, bool $isResidential): array
    {
        $packageLineItems = [
            "weight" => [
                "units" => UnitConstants::LB,
                "value" => $this->weight,
            ],
        ];

        if ($this->config->isIncorrectPackageCountToggleEnabled()) {
            $packageLineItems["groupPackageCount"] = $this->totalPackageCount > 0 ? $this->totalPackageCount : null;
        }

        $shipment = [
            "shipDateStamp" => $this->shipDate,
            "shipper" => $this->getShipperAddress($offerAddress, $regionCode),
            "recipient" => $this->getRecipientAddress($isResidential),
            "pickupType" => RequestConstants::PICK_UP_TYPE,
            "rateRequestType" => [
                !empty($this->shipAccountNumber) ? RequestConstants::ACCOUNT : RequestConstants::LIST,
            ],
            "requestedPackageLineItems" => [$packageLineItems],
            "preferredCurrency" => UnitConstants::USD,
        ];

        if ($this->config->isIncorrectPackageCountToggleEnabled()) {
            $shipment["totalWeight"] = $this->totalPackageCount > 0 ? ($this->totalPackageCount * $this->weight) : null;
        } else {
            $shipment["totalPackageCount"] = $this->totalPackageCount > 0 ? $this->totalPackageCount : null;
        }

        return $shipment;
    }

    private function getShipperAddress(array $offerAddress, string $regionCode): array
    {
        return [
            "address" => [
                "city" => $offerAddress['city'] ?? $this->shopData["additional_info"]['contact_info']['city'],
                "stateOrProvinceCode" => $regionCode,
                "postalCode" => $offerAddress['postalCode'] ?? $this->shopData["additional_info"]['contact_info']['zip_code'],
                "countryCode" => strtoupper($this->shopData["additional_info"]["shipping_zones"][0]),
            ],
        ];
    }

    private function getRecipientAddress(bool $isResidential): array
    {
        return [
            "address" => [
                "city" => $this->shippingAddress['city'],
                "stateOrProvinceCode" => $this->shippingAddress['region_code'],
                "postalCode" => $this->shippingAddress['postcode'],
                "countryCode" => $this->shippingAddress['country_id'],
                "residential" => $isResidential,
            ],
        ];
    }

    private function getCarrierCodes(): array
    {
        return [
            ShippingConstants::FDXE,
            ShippingConstants::FDXG,
        ];
    }

    /**
     * @param array $shop
     * @param array $offerAddress
     * @return string
     */
    private function getRegionCode(array $shop, array $offerAddress): string
    {
        $regionName = $offerAddress['stateOrProvinceCode'] ?? $shop["additional_info"]['contact_info']['state'];

        if (empty($regionName)) {
            return '';
        }

        $region = $this->collectionFactory->create()
            ->addRegionNameFilter($regionName)
            ->addCountryCodeFilter($shop["additional_info"]['contact_info']['country'])
            ->getFirstItem()
            ->toArray();

        return $region['code'] ?? '';
    }

    /**
     * @param string $request
     * @return bool
     */
    private function isResidentialAddress(string $request): bool
    {
        $requestData = json_decode($request, true);
        $customAttributes = $requestData['address']['custom_attributes'] ?? [];

        foreach ($customAttributes as $attribute) {
            if ($attribute['attribute_code'] === 'residence_shipping') {
                $residenceShippingValue = $attribute['value'];
                return $this->config->isIncorrectShippingTotalsToggleEnabled()
                    ? ($residenceShippingValue === true || $residenceShippingValue === 1)
                    : ($residenceShippingValue === true);
            }
        }

        return false;
    }

    /**
     * @param string $response
     * @return array
     */
    private function processResponse(string $response): array
    {
        $normalizedData = $this->jsonSerializer->unserialize($response);

        if (isset($normalizedData['output']['rateReplyDetails'])) {
            return $normalizedData['output']['rateReplyDetails'];
        }

        return [];
    }

}