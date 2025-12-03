<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceProduct\Model\Shop;
use Fedex\MarketplaceRates\Helper\Data;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;

class FreightCheckoutPricing
{
    private const LB = 'LB';
    private const IN = 'IN';
    private const LIFTGATE_DELIVERY = 'LIFTGATE_DELIVERY';

    public const BOX_TYPE = 'box';
    public const TOGGLE_D217815 = 'tiger_d217815';

    /**
     * @param Curl $curlClient
     * @param Json $jsonSerializer
     * @param LoggerInterface $logger
     * @param Data $helper
     * @param CollectionFactory $collectionFactory
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private Curl              $curlClient,
        private Json              $jsonSerializer,
        private LoggerInterface   $logger,
        private Data              $helper,
        private CollectionFactory $collectionFactory,
        private ToggleConfig      $toggleConfig,
    ) {
    }

    /**
     * @return mixed[]
     * @throws \Exception
     */
    public function execute(array $shopData, mixed $shippingAddress, string $shipDate, array $package, string $shipAccountNumber = '', bool $residentialValue = false): array
    {
        try {
            $regionCode = '';
            $regionName = $shopData['freight_state'];
            if (!empty($regionName)) {
                $region = $this->collectionFactory->create()
                    ->addRegionNameFilter($regionName)
                    ->addCountryCodeFilter('USA')
                    ->getFirstItem()
                    ->toArray();

                if (count($region) > 0) {
                    $regionCode = $region['code'];
                }
            }

            $setupURL = $this->helper->getFreightShippingRatesUrl();
            $gatewayToken = $this->helper->getFedexRatesToken();

            $headers = [
                "Content-Type: application/json",
                "Accept: application/json",
                "Accept-Language: json",
                "Authorization: Bearer " . $gatewayToken,
            ];

            $freightClass = $this->formatClassValue($package['freightClass']);
            if (!$freightClass) {
                throw new \Exception(
                    'Freight with invalid class: ' . $freightClass . ' for value: ' . $package['freightClass']
                );
            }

            $request = [
                "rateRequestControlParameters" => [
                    "returnTransitTimes" => true
                ],
                "accountNumber" => [
                    "value" => $shipAccountNumber
                ],
                "freightRequestedShipment" => [
                    "freightShipmentSpecialServices" => [
                        "specialServiceTypes" => [!$package['specialServices'] ? self::LIFTGATE_DELIVERY : '']
                    ],
                    "shipper" => [
                        "address" => [
                            "city" => $shopData['freight_city'],
                            "stateOrProvinceCode" => $regionCode,
                            "postalCode" => $shopData['freight_postcode'],
                            "countryCode" => 'US' //TODO
                        ]
                    ],
                    "recipient" => [
                        "address" => [
                            "residential" => $residentialValue,
                            "city" => $shippingAddress->getCity(),
                            "stateOrProvinceCode" => $shippingAddress->getRegionCode(),
                            "countryCode" => $shippingAddress->getCountryId(),
                            "postalCode" => $shippingAddress->getPostcode()
                        ]
                    ],
                    "shippingChargesPayment" => [
                        "paymentType" => "SENDER",
                        "payor" => [
                            "responsibleParty" => [
                                "accountNumber" => [
                                    "value" => $shopData['freight_account_number']
                                ]
                            ]
                        ]
                    ],
                    "freightShipmentDetail" => [
                        "accountNumber" => [
                            "value" => $shopData['freight_account_number']
                        ],
                        "fedExFreightBillingContactAndAddress" => [
                            "address" => [
                                "city" => $shopData['freight_city'],
                                "stateOrProvinceCode" => $regionCode,
                                "postalCode" => $shopData['freight_postcode'],
                                "countryCode" => 'US',
                                "residential" => false //TODO
                            ]
                        ],
                        "role" => "SHIPPER",
                        "totalHandlingUnits" => "1",
                        "lineItem" => [
                            [
                                "freightClass" => $freightClass,
                                "id" => $package['type'],
                                "weight" => [
                                    "value" => $package['weight'],
                                    "units" => self::LB
                                ],
                                "dimensions" => [
                                    "length" => $package['shape']['length'],
                                    "width" => $package['shape']['width'],
                                    "depth" => $package['shape']['depth'],
                                    "volume" => $package['shape']['volume'],
                                    "area" => $package['shape']['area'],
                                    "units" => self::IN
                                ]
                            ]
                        ]
                    ],
                    "requestedPackageLineItems" => [
                        [
                            "associatedFreightLineItems" => [
                                [
                                    "id" => $package['type']
                                ]
                            ],
                            "weight" => [
                                "units" => self::LB,
                                "value" => $package['weight']
                            ],
                            "subPackagingType" => "BUNDLE"
                        ]
                    ],
                    "rateRequestType" => $this->getRateRequestType(),
                    "shipDateStamp" => $shipDate
                ]
            ];

            if (isset($package['quantity']) > 0) {
                $request['freightRequestedShipment']['freightShipmentDetail']['totalPackageCount'] = $package['quantity'];
                $request['freightRequestedShipment']['requestedPackageLineItems'][0]['groupPackageCount'] = $package['quantity'];
            }

            $jsonBody = $this->jsonSerializer->serialize($request);

            $this->curlClient->setOptions(
                [
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_ENCODING => '',
                ]
            );

            $this->logger->info(
                __METHOD__ . ':' . __LINE__ . 'Freight API request start'
            );
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .
                'Freight API request payload: ' . $jsonBody
            );

            $this->curlClient->post($setupURL, $jsonBody);

            $response = $this->curlClient->getBody();

            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .
                'Freight API response payload: ' . $response
            );

            if ($this->curlClient->getStatus() === 200) {
                $normalizedData = $this->jsonSerializer->unserialize($response);

                if (isset($normalizedData['output']['rateReplyDetails'])) {
                    return $normalizedData['output']['rateReplyDetails'];
                }
            }

        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ': ' . $e->getMessage());
        }

        return [];
    }

    protected function formatClassValue($value) {
        $normalizedValue = floatval($value);

        if ($normalizedValue > 0) {
            if (is_int($normalizedValue) || $normalizedValue == intval($normalizedValue)) {
                return sprintf("CLASS_%03d", $normalizedValue);
            }

            if (is_float($normalizedValue)) {
                $parts = explode('.', str_replace(',', '.', (string)$normalizedValue));
                return sprintf("CLASS_%03d_%s", $parts[0], $parts[1]);
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    protected function getRateRequestType() {
        if($this->toggleConfig->getToggleConfigValue(self::TOGGLE_D217815)) {
            return ["PREFERRED"];
        }

        return ["ACCOUNT"];
    }
}
