<?php

declare(strict_types=1);

namespace Fedex\ShippingEstimator\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    public const XML_PATH_FEDEX_SHIPP_DELIV_REF = 'fedex_shipping_estimator/estimator/delivery_reference';
    public const XML_PATH_FEDEX_SHIPP_ADDR_CLASSI = 'fedex_shipping_estimator/estimator/address_classification';
    public const XML_PATH_FEDEX_SHIPP_CHEAP_LBL = 'fedex_shipping_estimator/estimator/cheapest_delivery_lbl';
    public const XML_PATH_FEDEX_SHIPP_FAST_LBL = 'fedex_shipping_estimator/estimator/fastest_delivery_lbl';
    public const XML_PATH_FEDEX_GEL_DELIVRY_URL = 'fedex/general/delivery_api_url';
    public const ESTIMATED_SHIPPING_RATE = 'estimatedShipmentRate';
    public const ESTIMATED_DELIVERY_DURATION = 'estimatedDeliveryDuration';
    public const ESTIMATED_DELIVERY_LOCALTIME = 'estimatedDeliveryLocalTime';
    public const ESTIMATED_SHIP_DATE = 'estimatedShipDate';
    public const SERVICE_DESCRIPTION = 'serviceDescription';
    public const VALUE = 'value';
    public const OPTIONS_WITH_DELIVERY_DURATION = ['FEDEX_HOME_DELIVERY', 'GROUND_US'];
    public const DATE_FORMAT_TYPE = 'l, F j';
    public const ARRIVES_BY = 'Arrives by ';

    /**
     * Data constructor.
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * @param array $params
     * @return array
     */
    public function createRequestPayload(array $params)
    {
        $products = json_decode($params['products']);
        $postalCode = $params['postalCode'];
        $stateOrProvinceCode = $params['stateOrProvinceCode'];
        $validateContent = filter_var($params['validateContent'], FILTER_VALIDATE_BOOLEAN);
        $productAssociations = [];
        foreach ($products as $product) {
            $productAssociations[] = ['id' => $product->instanceId, 'quantity' => $product->qty];
        }

        $deliveries = [
            [
                'deliveryReference' => 'default',
                'address' => [
                    'streetLines' => [],
                    'city' => 'null',
                    'countryCode' => 'US',
                    'stateOrProvinceCode' => $stateOrProvinceCode,
                    'postalCode' => $postalCode,
                    'addressClassification' => 'Home',
                ],
                'requestedDeliveryTypes' => [
                    'requestedShipment' => [
                        'productionLocationId' => null,
                        'fedExAccountNumber' => null,
                    ]
                ],
                'productAssociations' => $productAssociations,
            ]
        ];

        return [
            'deliveryOptionsRequest' => [
                'fedExAccountNumber' => null,
                'site' => null,
                'products' => $products,
                'deliveries' => $deliveries,
                'validateContent' => $validateContent
            ]
        ];
    }

    /**
     * @param $result
     * @return array
     */
    public function formatResult($result)
    {
        $deliveryOption = $result['output']['deliveryOptions'][0]['shipmentOptions'];
        if (empty($deliveryOption)) {
            return [
                'cheapest_delivery' => '',
                'fastest_delivery' => ''
            ];
        }
        $cheapestDelivery = $this->getCheapestDelivery($deliveryOption);

        return [
            'cheapest_delivery' => $cheapestDelivery['deliveryOption'],
            'fastest_delivery' => $this->getFastestDelivery($cheapestDelivery['remainingArray'])
        ];
    }

    /**
     * @param $deliveryOption
     * @return array
     */
    public function getCheapestDelivery($deliveryOption)
    {
        usort($deliveryOption, function ($a, $b) {
            return $a[self::ESTIMATED_SHIPPING_RATE] - $b[self::ESTIMATED_SHIPPING_RATE];
        });

        $cheapDeliveryOption = array_slice($deliveryOption, 0, 3);
        foreach ($cheapDeliveryOption as $key => $value) {
            unset($deliveryOption[$key]);
        }

        return [
            'deliveryOption' => $this->setDelivery($cheapDeliveryOption),
            'remainingArray' => $deliveryOption
        ];
    }

    /**
     * @param $deliveryOption
     * @return array
     */
    public function getFastestDelivery($deliveryOption)
    {
        if (!empty($deliveryOption)) {
            $cnt = 0;
            foreach ($deliveryOption as $option) {
                if (isset($option[self::ESTIMATED_DELIVERY_DURATION][self::VALUE])) {
                    $deliveryOption[$cnt][self::ESTIMATED_DELIVERY_LOCALTIME] =
                    date(
                        'Y-m-d g:i:s',
                        strtotime($option[self::ESTIMATED_SHIP_DATE]
                        . ' +' . $option[self::ESTIMATED_DELIVERY_DURATION][self::VALUE]
                        . ' day')
                    );
                }
            }
            usort($deliveryOption, function ($a, $b) {
                return strtotime($a[self::ESTIMATED_DELIVERY_LOCALTIME]) -
                strtotime($b[self::ESTIMATED_DELIVERY_LOCALTIME]);
            });
            $fastestDeliveryOption = array_slice($deliveryOption, 0, 1);

            return $this->setDelivery($fastestDeliveryOption);
        }

        return '';
    }

    /**
     * @param $options
     * @return array
     */
    public function setDelivery($options)
    {
        $deliver = [];
        foreach ($options as $option) {
            $label = (strpos($option[self::SERVICE_DESCRIPTION], 'FedEx') === false) ? 'FedEx '
            . $option[self::SERVICE_DESCRIPTION] : $option[self::SERVICE_DESCRIPTION];

            $value = [];
            $estimatedShipmentRate = 0;
            if (isset($option['estimatedShipmentRate'])) {
                $estimatedShipmentRate = floatval($option[self::ESTIMATED_SHIPPING_RATE]);
            }
            $value['price'] = [
                self::VALUE => number_format($estimatedShipmentRate, 2),
                'currencySymbol' => '$',
                'currencyCode' => $option['currency']
            ];

            if (!in_array($option['serviceType'], self::OPTIONS_WITH_DELIVERY_DURATION)) {
                $description = $this->getEstimatedDateTimeForLocalDelivery($option);
            } else {
                if ($estimatedShipmentRate == 0) {
                    $value['price'] = [
                        self::VALUE => 'Free',
                        'currencySymbol' => '',
                        'currencyCode' => $option['currency']
                    ];
                }

                $description = $this->getEstimateDateTimeDescription($option);
            }

            $value['label'] = $label;
            $value['description'] = $description;
            $deliver[] = $value;
        }

        return $deliver;
    }

    /**
     * @param array $option
     *
     * @return string
     */
    public function getEstimatedDateTimeForLocalDelivery($option)
    {
        $newDatetimeFormat = $this->updatedDateTimeFormat($option);
        $description = self::ARRIVES_BY. $newDatetimeFormat;

        return $description;
    }

    /**
     * @param array $option
     *
     * @return string
     */
    public function getEstimateDateTimeDescription($option)
    {
        if (!empty($option[self::ESTIMATED_DELIVERY_LOCALTIME])) {
            $newDatetimeFormat = $this->updatedDateTimeFormat($option);

            return self::ARRIVES_BY. $newDatetimeFormat;
        } else {
            $date = date_create(
                date(
                    'Y-m-j',
                    strtotime(
                        $option[self::ESTIMATED_DELIVERY_DURATION][self::VALUE]. ' weekdays',
                        strtotime($option[self::ESTIMATED_SHIP_DATE])
                    )
                )
            );

            return 'Arrives 1 business day after ' . date_format($date, self::DATE_FORMAT_TYPE);
        }
    }

    /**
     * @param array $option
     *
     * @return string
     */
    public function updatedDateTimeFormat($option)
    {
        return date(self::DATE_FORMAT_TYPE, strtotime($option[self::ESTIMATED_DELIVERY_LOCALTIME]))
        .', '.strtolower(date("g:ia", strtotime($option[self::ESTIMATED_DELIVERY_LOCALTIME])));
    }

    /**
     * @return string
     */
    public function getDeliveryReference()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_SHIPP_DELIV_REF,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    public function getAddressClassification()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_SHIPP_ADDR_CLASSI,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    public function getdeliveryApiUrl()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_GEL_DELIVRY_URL,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    public function getCheapestDeliveryLabel()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_SHIPP_CHEAP_LBL,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    public function getFastestDeliveryLabel()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_SHIPP_FAST_LBL,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
