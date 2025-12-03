<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceRates\Helper;

use Exception;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceHelper;
use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;
use Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\ConfigurationMismatchException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Store\Model\ScopeInterface;
use \Magento\Customer\Model\Session;
use Psr\Log\LoggerInterface;
use \Magento\Framework\HTTP\Client\Curl;
use \Magento\Framework\Encryption\EncryptorInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Fedex\MarketplaceCheckout\Helper\Data as Helper;

class Data extends AbstractHelper
{
    /**
     * Number of maximum retries.
     */
    private const MAX_RETRIES = 3;
    private const TIGER_D_216504 = 'tiger_d_216504';
    private const TIGER_D_2255568 = 'tiger_team_d225568_3p_cbb_packaging_data';

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param ScopeConfigInterface $configInterface
     * @param Session $customerSession
     * @param LoggerInterface $logger
     * @param Curl $curl
     * @param EncryptorInterface $encryptorInterface
     * @param HandleMktCheckout $handleMktCheckout
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        private ScopeConfigInterface                  $configInterface,
        private Session                               $customerSession,
        private LoggerInterface                       $logger,
        private Curl                                  $curl,
        private EncryptorInterface                    $encryptorInterface,
        private readonly HandleMktCheckout    $handleMktCheckout,
        private MarketplaceHelper             $marketPlaceHelper,
        private OrderItemRepositoryInterface  $orderItemRepository,
        private Helper                        $helper,
        private ToggleConfig $toggleConfig
    ) {
        parent::__construct($context);
    }

    /**
     * Token API To Get Fedex Rates Token.
     *
     * @return String
     */
    public function getFedexRatesToken()
    {
        if ($this->isFedexRatesTokenExpired()) {

            $apiURL = $this->getTokenUrl();
            $id     = $this->getClientId();
            $secret = $this->getClientSecret();

            try {

                if (!$apiURL || !$id || !$secret) {
                    throw new ConfigurationMismatchException(__('Missing Gateway Token Configuration!'));
                }

                $headers = [
                    "Content-Type: application/x-www-form-urlencoded",
                    "Connection: Keep-Alive",
                    "Keep-Alive: 300"
                ];

                $params = [
                    'grant_type'    => 'confidential_client',
                    'client_id'     => $id,
                    'client_secret' => $secret
                ];

                $paramString = http_build_query($params);
                $postData    = json_encode($params);

                $this->curl->setOptions(
                    [
                        CURLOPT_CUSTOMREQUEST => "POST",
                        CURLOPT_POSTFIELDS => $paramString,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER => $headers
                    ]
                );

                if ($this->toggleConfig->getToggleConfigValue('hawks_d_206910_fix_infinite_loop')) {
                    $this->curl->post($apiURL, $postData);
                    $response = $this->curl->getBody();
                    $decodedResponse = json_decode($response, true);
                    if (isset($decodedResponse['error']) || !isset($decodedResponse['access_token'])) {
                        $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Failed to fetch access_token ' . $response);
                        throw new LocalizedException(__($response));
                    }

                    $gatewayToken = $decodedResponse['access_token'];
                    $this->setFedexRatesTokenInfo($gatewayToken, $decodedResponse);
                } else {
                    $requestCounter = 0;
                    do {
                        $requestCounter++;
                        $this->curl->post($apiURL, $postData);
                        $response = json_decode($this->curl->getBody(), true);

                        if (isset($response['error']) && $requestCounter == self::MAX_RETRIES) {
                            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $response['error']);
                            throw new LocalizedException(__($response['error']));
                        }

                        $gatewayToken = $response['access_token'] ?? null;
                        $this->setFedexRatesTokenInfo($gatewayToken, $response);

                    } while (!$gatewayToken);
                }

            } catch (Exception $e) {
                $this->logger->critical(
                    __METHOD__ . ':' . __LINE__ . ' Gateway Fedex Rates Token API Error: ' . $e->getMessage()
                );
                return null;
            }
        }

        return $this->customerSession->getFedexRatesToken();
    }

    /**
     * Call Fedex Rates API.
     * @param bool $customerShippingAccount3PEnabled
     * @param string $shipAccountNumber
     * @param string|null $data
     * @return string
     */
    public function getResponseFromFedexRatesAPI(bool $customerShippingAccount3PEnabled, string $shipAccountNumber, string $data = null)
    {
        $setupURL = $this->getShippingRatesUrl();
        $gateWayToken = $this->getFedexRatesToken();

        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "Authorization: Bearer " . $gateWayToken,
        ];

        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => '',
            ]
        );

        $this->curl->post($setupURL, $data);
        $this->logger->info(
            __METHOD__ . ':' . __LINE__ . ' Before Fedex Rates Call API:'
            . ' Request Data => ' . $data
        );
        $output = $this->curl->getBody();
        $this->logger->info(
            __METHOD__ . ':' . __LINE__ . ' After Fedex Rates Call API:'
            . ' Response Data => ' . $output
        );

        $normalizedData = json_decode($output, true);
        if (isset($normalizedData['output']['rateReplyDetails'])) {
            return $normalizedData['output']['rateReplyDetails'];
        }

        return '';
    }

    /**
     * Response from Fedex Rates request
     *
     * @param string $gatewayToken
     * @param array $response
     * @return void
     */
    public function setFedexRatesTokenInfo($gatewayToken, $response): void
    {
        if ($gatewayToken) {
            $this->customerSession->setFedexRatesToken($gatewayToken);
            $this->customerSession->setFedexRatesExpirationTime(time() + $response["expires_in"] ?? 3600);
        }
    }

    /**
     * Check if Fedex Rates Token is still valid
     *
     * @return bool
     */
    private function isFedexRatesTokenExpired(): bool
    {
        $expirationTime = $this->customerSession->getFedexRatesExpirationTime();
        if ($expirationTime && $expirationTime > time()) {
            return false;
        }
        return true;
    }

    /**
     * Handles MethodTitle to insert prefix or not
     *
     * @param $methoTitle
     * @return string
     */
    public function handleMethodTitle($methoTitle): string
    {
        if (str_contains($methoTitle, 'FedEx') || str_contains($methoTitle, 'Fedex')) {
            return $methoTitle;
        } else {
            return $this->getPrefixShippingMethodName() . ' ' . $methoTitle;
        }
    }

    /**
     * Get token URL.
     *
     * @return mixed
     */
    private function getTokenUrl()
    {
        return $this->configInterface->getValue(
            "fedex/fedex_rate_quotes/token_url",
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Shipping Rates URL.
     *
     * @return mixed
     */
    public function getShippingRatesUrl()
    {
        return $this->configInterface->getValue(
            "fedex/fedex_rate_quotes/shipping_rates_url",
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Client ID.
     *
     * @return mixed
     */
    private function getClientId()
    {
        return $this->encryptorInterface->decrypt(
            $this->configInterface->getValue(
                "fedex/fedex_rate_quotes/client_id",
                ScopeInterface::SCOPE_STORE
            )
        );
    }

    /**
     * Get Client Secret.
     *
     * @return mixed
     */
    private function getClientSecret()
    {
        return $this->encryptorInterface->decrypt(
            $this->configInterface->getValue(
                "fedex/fedex_rate_quotes/client_secret",
                ScopeInterface::SCOPE_STORE
            )
        );
    }

    /**
     * Get Prefix Shipping Method Name.
     *
     * @return string
     */
    public function getPrefixShippingMethodName()
    {
        return $this->configInterface->getValue(
            "fedex/fedex_rate_quotes/prefix_shipping_method_name",
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get MKT shipping amount.
     *
     * @param $order
     * @param null $shipping
     * @return mixed|void
     */
    public function getMktShippingTotalAmount($order, $shipment = null)
    {
        $miraklShippingTotal = 0;
        $allItems = $order->getAllItems();
        $filteredItems = array_filter($allItems, function ($item) {
            return $item->getMiraklShopId() != null;
        });
        if (count($filteredItems) > 1) {
            // View Shipment Page in admin
            if ($shipment) {
                $shipmentItems = $shipment->getItems();
                foreach ($shipmentItems as $shipmentItem) {
                    $orderItem = $this->orderItemRepository->get($shipmentItem->getOrderItemId());
                    $additionalData = json_decode($orderItem->getAdditionalData(), true);
                    if (!isset($additionalData['mirakl_shipping_data']['amount'])) {
                        continue;
                    }
                    $miraklShippingTotal = $additionalData['mirakl_shipping_data']['amount'];
                    break;
                }
            } else {
                $miraklShippingTotal = $order->getMiraklShippingFee();
            }
        } else {
            $filteredItems = array_values($filteredItems);
            $miraklShippingTotal = 0;
            foreach ($filteredItems as $item) {
                /** @var CartItemInterface $item */
                $additionalData = json_decode($item->getAdditionalData(), true);
                if (!isset($additionalData['mirakl_shipping_data']['amount'])) {
                    continue;
                }
                $miraklShippingTotal += $additionalData['mirakl_shipping_data']['amount'] ?? 0;
            }
        }

        return $miraklShippingTotal;
    }

    /**
     * Get MKT shipping amount per Item/Seller
     *
     * @param $order
     * @return array|void
     */
    public function getMktShippingTotalAmountPerItem($order)
    {
        $allItems      = $order->getAllItems();
        $filteredItems = array_filter($allItems, function ($item) {
            return $item->getMiraklShopId() != null;
        });
        $filteredItems = array_values($filteredItems);
        $miraklShippingPerItem = [];
        foreach ($filteredItems as $item) {
            /** @var CartItemInterface $item */
            $additionalData = json_decode($item->getAdditionalData(), true);
            if (!isset($additionalData['mirakl_shipping_data']['amount'])) {
                continue;
            }
            $miraklShippingPerItem[$item->getItemId()] = $additionalData['mirakl_shipping_data']['amount'] ?? 0;
        }

        return $miraklShippingPerItem;
    }

    /**
     * Get MKT shipping amount.
     *
     * @param $order
     * @return mixed|void
     */
    public function getMktShippingAddress($order)
    {
        $allItems      = $order->getAllItems();
        $filteredItems = array_filter($allItems, function ($item) {
            return $item->getMiraklShopId() != null;
        });
        $filteredItems = array_values($filteredItems);

        foreach ($filteredItems as $item) {
            /** @var CartItemInterface $item */
            $additionalData = json_decode($item->getAdditionalData(), true);

            if (!isset($additionalData['mirakl_shipping_data']['address'])) {
                continue;
            }
            return $additionalData['mirakl_shipping_data']['address'];
        }
    }


    /**
     * Get MKT shipping data.
     *
     * @param $order
     * @param null $currentItem
     * @param null $shipment
     * @return mixed|void
     */
    public function getMktShipping($order, $currentItem = null, $shipment = null)
    {
        $sellerId = null;
        $allItems = $order->getAllItems();
        $filteredItems = array_filter($allItems, function ($item) {
            return $item->getMiraklShopId() != null;
        });

        if (count($filteredItems) > 1) {
            // View Shipment Page in admin
            if ($shipment) {
                $shipmentItems = $shipment->getItems();
                foreach ($shipmentItems as $shipmentItem) {
                    $orderItem = $this->orderItemRepository->get($shipmentItem->getOrderItemId());
                    $additionalData = json_decode($orderItem->getAdditionalData(), true);
                    if (!isset($additionalData['mirakl_shipping_data'])) {
                        continue;
                    }
                    return $additionalData['mirakl_shipping_data'];
                }
            } elseif ($currentItem) {
                $additionalData = json_decode($currentItem->getAdditionalData(), true);
                return $additionalData['mirakl_shipping_data'];
            }
        } else {
            $filteredItems = array_values($filteredItems);
            if ($currentItem && $currentItem->getData('mirakl_shop_id')) {
                $sellerId = $currentItem->getData('mirakl_shop_id');
            }
            foreach ($filteredItems as $item) {
                /** @var CartItemInterface $item */
                $additionalData = json_decode($item->getAdditionalData(), true);
                if (!isset($additionalData['mirakl_shipping_data'])) {
                    continue;
                }
                if (!is_null($sellerId) && isset($additionalData['mirakl_shipping_data']['seller_id'])) {
                    if ($sellerId == $additionalData['mirakl_shipping_data']['seller_id']) {
                        return $additionalData['mirakl_shipping_data'];
                    }
                }
                if (is_null($sellerId)) {
                    return $additionalData['mirakl_shipping_data'];
                }
            }
        }
        return null;
    }


    /**
     * Get MKT shipping data.
     *
     * @param $order
     * @param null $shipment
     * @return mixed|void
     */
    public function getOrderItemMiraklShippingData($shipment)
    {
        if ($shipment) {
            $shipmentItems = $shipment->getItems();
            foreach ($shipmentItems as $shipmentItem) {
                $orderItem = $this->orderItemRepository->get($shipmentItem->getOrderItemId());
                $additionalData = json_decode($orderItem->getAdditionalData(), true);
                if (!isset($additionalData['mirakl_shipping_data'])) {
                    continue;
                }
                return $additionalData['mirakl_shipping_data'];
            }
        }
        return null;
    }

    /**
     * Get Freight Shipping Rates URL
     *
     * @return string
     */
    public function getFreightShippingRatesUrl(): string
    {
        return $this->configInterface->getValue(
            "fedex/fedex_freight_rate_quotes/shipping_rates_url",
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Is Freight Shipping enabled
     *
     * @return bool
     */
    public function isFreightShippingEnabled(): bool
    {
        return $this->configInterface->isSetFlag(
            "fedex/fedex_freight_rate_quotes/shipping_rates_enabled",
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Freight Shipping Surcharge text
     *
     * @return string
     */
    public function getFreightShippingSurchargeText(): string
    {
        return $this->configInterface->getValue(
            "fedex/fedex_freight_rate_quotes/surcharge_text",
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    /**
     * @return bool
     */
    public function isd216504toggleEnabled(){
        return (bool) $this->toggleConfig->getToggleConfigValue(self::TIGER_D_216504);
    }
    /**
     * @return bool
     */
    public function isd2255568toggleEnabled(){
        return (bool) $this->toggleConfig->getToggleConfigValue(self::TIGER_D_2255568);
    }

}
