<?php
/**
 * @category    Fedex
 * @package     Fedex_ShippingAddressValidation
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Pooja Tiwari <pooja.tiwari.osv@fedex.com>
 */
declare (strict_types = 1);

namespace Fedex\ShippingAddressValidation\Helper;

use Fedex\MarketplaceRates\Helper\Data as MarketplaceHelperData;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class Data extends AbstractHelper
{
    public const OUTPUT = 'output';
    private const ERRORS = 'errors';
    private const COUNTRY_CODE = 'US';
    /**
     * @var string[]
     */
    public array $formData;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $configInterface
     * @param Curl $curl
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        private ScopeConfigInterface $configInterface,
        private Curl $curl,
        private MarketplaceHelperData $purpleGatewayToken,
        private LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * Curl Post Data
     *
     * @param string|array
     */
    public function callAddressValidationApi($postData)
    {
        $gatewayToken = $this->purpleGatewayToken->getFedexRatesToken();
        $url = $this->getShippingAddressUrl();
        $dataString = $this->prepareData($postData);

        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $gatewayToken,
        ];
        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $dataString,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => '',
            ]
        );

        try {
            $this->curl->post($url, $dataString);
            $output = $this->curl->getBody();
            $response = json_decode($output, true);

            if (isset($response[self::ERRORS]) || !isset($response[self::OUTPUT])) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Shipping Address Validation API Request:');
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $dataString);
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Shipping Address Validation API Response:');
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $output);
            }
            return $response;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
                ' Exception occurred while pulling Shipping Address API: ' . $e->getMessage());
        }
    }

    /**
     * Get Shipping Address url
     *
     * @return string
     */
    public function getShippingAddressUrl()
    {
        return $this->configInterface->getValue('fedex/general/shipping_address_api_url', ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get Reponse Value from key
     * @param  array $response
     * @param  string $addresskey
     * @return array
     */
    public function getResponseValue($response, $addresskey)
    {
        if (isset($response['output']['resolvedAddresses'])) {
            $resolvedAddresses = $response['output']['resolvedAddresses'];
            foreach ($resolvedAddresses as $address) {
                foreach ($address as $key => $value) {
                    if ($key == $addresskey) {
                        return $value;
                    }
                }
            }
        }
        return [];
    }

    /**
     * To prepare Shipping Address API request data.
     *
     * @param array $postData
     * @return string
     */
    public function prepareData($postData)
    {
        $personName = (isset($postData['firstName']) ? $postData['firstName'] : '')
            . ' ' . (isset($postData['lastName']) ? $postData['lastName'] : '');
        $phoneNumber = isset($postData['phoneNumber']) ? $postData['phoneNumber'] : '';
        $streetLines = isset($postData['streetLines']) ? $postData['streetLines'] : '';
        $city = isset($postData['city']) ? $postData['city'] : '';
        $postalCode = isset($postData['zipcode']) ? $postData['zipcode'] : '';

        $dataArr = [
            'validateAddressControlParameters' => [
                'includeResolutionTokens' => true,
            ],
            'addressesToValidate' => [
                [
                    'contact' => [
                        'personName' => $personName,
                        'phoneNumber' => $phoneNumber,
                    ],
                    'address' => [
                        'streetLines' => $streetLines,
                        'city' => $city,
                        'postalCode' => $postalCode,
                        'countryCode' => self::COUNTRY_CODE,
                    ],
                ],
            ],
        ];
        return json_encode($dataArr);
    }

    public function getShippingAddressErrorCode()
    {
        return $this->configInterface->getValue('fedex/general/shipping_address_api_error_code', ScopeInterface::SCOPE_STORE);
    }
}
