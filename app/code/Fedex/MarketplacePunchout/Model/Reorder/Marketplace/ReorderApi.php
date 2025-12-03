<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplacePunchout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Jyoti thakur <jyoti.thakur.osv@fedex.com>
 */

namespace Fedex\MarketplacePunchout\Model\Reorder\Marketplace;

use Exception;
use Fedex\MarketplaceCheckout\Helper\Data as MarketPlaceHelper;
use Fedex\MarketplacePunchout\Model\Authorization;
use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;
use Fedex\MarketplacePunchout\Api\ReorderApiInterface;
use Fedex\TrackOrder\Model\OrderDetailsDataMapper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Checkout\Model\Session as CheckoutSession;
use Psr\Log\LoggerInterface;
use Mirakl\Api\Helper\Shipment as ShipmentApi;
use Mirakl\Api\Helper\Order as MiraklHelper;
use Fedex\Orderhistory\ViewModel\OrderHistoryEnhacement;

class ReorderApi implements ReorderApiInterface
{
    const STATUS_CODE_500 = 500;

    const STATUS_CODE_200 = 200;

    /**
     * @param MarketplaceConfig $config
     * @param Curl $curl
     * @param LoggerInterface $logger
     * @param Authorization $authorization
     * @param CheckoutSession $checkoutSession
     * @param OrderDetailsDataMapper $dataMapper
     * @param MarketPlaceHelper $marketPlaceHelper
     * @param ShipmentApi $shipmentApi
     * @param MiraklHelper $miraklHelper
     * @param OrderHistoryEnhacement $orderHistoryEnhancement
     */
    public function __construct(
        private MarketplaceConfig      $config,
        private Curl                   $curl,
        private LoggerInterface        $logger,
        private Authorization          $authorization,
        private CheckoutSession        $checkoutSession,
        private OrderDetailsDataMapper $dataMapper,
        private MarketPlaceHelper      $marketPlaceHelper,
        private ShipmentApi            $shipmentApi,
        private MiraklHelper           $miraklHelper,
        private OrderHistoryEnhacement $orderHistoryEnhancement
    ) {
    }

    /**
     * Implement getReorderApiData() method
     *
     * @param string $brokerConfigId
     * @param string $productSku
     * @param string $orderIncrementId
     * @param int|null $itemQty
     * @param int|null $orderItemId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getReorderApiData(string $brokerConfigId, string $productSku, string $orderIncrementId, ?int $itemQty = null, int $orderItemId = null): string
    {
        if ($brokerConfigId) {
            $retries = 3;
            $attempt = 1;
            $reOrderUrl = $this->config->getNavitorReorderUrl();
            $actionString = 'ReorderNoEdit';
            $authorizationUrl =
            $token = $sellerHeader = '';
            $productDesignId = $brokerConfigId;
            $shopCustomAttributes = $this->config->getShopCustomAttributesByProductSku($productSku);
            if ($shopCustomAttributes) {
                $reOrderUrl = $shopCustomAttributes['reorder-url'];
                $actionString = $shopCustomAttributes['allow-edit-reorder'] === 'true' ? 'ReorderEdit' : 'ReorderNoEdit';
                if (isset($shopCustomAttributes['authorization-url'])) {
                    $authorizationUrl = $shopCustomAttributes['authorization-url'];
                }
                if (isset($shopCustomAttributes['shared-secret-token']) && !isset($shopCustomAttributes['authorization-url'])) {
                    $token = $shopCustomAttributes['shared-secret-token'];
                    if ($shopCustomAttributes['mirakl-order-id-for-reorder'] === 'true') {
                        $brokerConfigId = $this->dataMapper->getMiraklOrderValue($orderIncrementId, $shopCustomAttributes['shop_id'], $shopCustomAttributes['offer_id'], true);
                    }
                } else {
                    $token = $this->getAuthorizationToken($productSku);
                }
                if (isset($shopCustomAttributes['seller-headers'])) {
                    $sellerHeader = $shopCustomAttributes['seller-headers'];
                }
            }


            do {
                try {
                    $headers = [
                        "Content-Type: application/json",
                        "Accept-Language: json",
                        "Authorization: Bearer " . $token
                    ];
                    if (!empty($sellerHeader)) {
                        if (!is_object($sellerHeader)) {
                            $sellerHeader = json_decode($sellerHeader);
                        }
                        foreach ($sellerHeader as $key => $value) {
                            $headers[] = $key . ":" . $value;
                        }
                    }

                    // Non Printful type of sellers
                    if (!empty($authorizationUrl)) {
                        $postFields = [
                            [
                                'action' => $actionString,
                                'brokerConfigId' => $brokerConfigId,
                                'punchoutBy' => 'SKU'
                            ]
                        ];
                    } /* Printful type of sellers */ else {

                        if (filter_var($shopCustomAttributes['mirakl-order-id-for-reorder'] ?? null, FILTER_VALIDATE_BOOLEAN)) {
                            $postFields = [
                                'action' => $actionString,
                                'brokerConfigId' => $brokerConfigId,
                                'designId' => $productDesignId,
                                'punchoutBy' => 'SKU',
                                'quantity' => $itemQty
                            ];

                        } else {
                            $postFields = [
                                'action' => $actionString,
                                'brokerConfigId' => $brokerConfigId,
                                'punchoutBy' => 'SKU',
                                'quantity' => $itemQty
                            ];
                        }

                    }
                    $postFields = json_encode($postFields);


                    $this->curl->setOptions(
                        [
                            CURLOPT_CUSTOMREQUEST => "POST",
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_HTTPHEADER => $headers,
                            CURLOPT_ENCODING => '',
                            CURLOPT_POSTFIELDS => $postFields,

                        ]
                    );

                    $this->curl->post($reOrderUrl, $postFields);
                    $status = $this->curl->getStatus();
                    $responseJson = $this->curl->getBody();

                    if ($responseJson !== null) {
                        $response = json_decode($responseJson, true);
                    } else {
                        $response = [];
                    }
                    if ($status >= 400) {
                        if ($status == 401) {
                            $token = $this->setNewTokenSession($productSku);
                        }
                        $attempt++;
                    } else {
                        break;
                    }
                    $this->logger->info("SELLER INFO Reorder Api : Curl BrokerConfigId " . $brokerConfigId);
                    $this->logger->info("SELLER INFO Reorder Api : Curl status " . $status);
                } catch (Exception $e) {
                    $this->logger->info(
                        "SELLER INFO: Exception on Reorder Api for brokerConfigId" . $brokerConfigId . " : " . $e->getMessage()
                    );
                    $status = static::STATUS_CODE_500;
                    $response = $e->getMessage();
                    $attempt++;
                }
            } while ($attempt <= $retries);

            if ($status === self::STATUS_CODE_200) {
                // Printful type of sellers
                if (empty($authorizationUrl)) {
                    if (isset($response['result'])) {
                        $responseTemp[0] = $response['result'];
                        $responseTemp[0]['isSuccess'] = true;
                        $response = $responseTemp;
                    }
                }
            }

            $response = array(
                'status' => $status,
                'response' => $response
            );
        } else {
            $response = [
                "status" => self::STATUS_CODE_200,
                "response" => [
                    [
                        "code" => self::STATUS_CODE_200,
                        "result" => [],
                        "error" => [],
                        "isSuccess" => true
                    ]
                ]
            ];
        }

        if ($orderItemId) {
            $response['track'] = $this->getShipmentTrackingNumbersByOrderItem($orderIncrementId, $orderItemId);
        }

        return json_encode($response);
    }

    /**
     * @param $productSku
     * @return string|null
     * @throws Exception
     */
    private function getAuthorizationToken($productSku = null): ?string
    {
        $token = $this->checkoutSession->getMarketplaceAuthToken();
        if (!$token) {
            $token = $this->setNewTokenSession($productSku);
        }
        return $token;
    }

    /**
     * @param $productSku
     * @return string|null
     */
    private function setNewTokenSession($productSku = null): ?string
    {
        try {
            $token = $this->authorization->execute($productSku);
            $this->checkoutSession->setMarketplaceAuthToken($token);
            return $token;
        } catch (Exception $e) {
            $this->logger->info(
                "SELLER INFO: Error on Reorder Api for generate authentication token: " . $e->getMessage()
            );
        }
        return null;
    }

    /**
     * Gets shipment tracking numbers by order item
     * @param string $orderIncrementId
     * @param int $orderItemId
     * @return mixed[]
     */
    public function getShipmentTrackingNumbersByOrderItem(string $orderIncrementId, int $orderItemId): array
    {
        if (!$this->marketPlaceHelper->isEssendantToggleEnabled() || !$orderItemId) {
            return [];
        }

        $trackNumbers = $miraklOrderIds = [];
        $miraklOrders = $this->miraklHelper->getOrders(
            [
                'commercial_ids' => $orderIncrementId
            ]
        );

        foreach ($miraklOrders as $miraklOrder) {
            $miraklOrderIds[] = $miraklOrder->getId();
        }

        if (empty($miraklOrderIds)) {
            return [];
        }

        $trackingUrl = $this->orderHistoryEnhancement->getTrackOrderUrl();
        $shipments = $this->shipmentApi->getShipments($miraklOrderIds);
        if ($shipments && count($shipments->getCollection())) {
            foreach ($shipments->getCollection() as $shipment) {
                $tracking = $shipment->getData('tracking');
                $shipmentLines = $shipment->getData('shipment_lines');
                foreach ($shipmentLines as $shipmentItem) {
                    $orderItem = $shipmentItem->getData('order_line_id');
                    if ($orderItem == $orderItemId) {
                        $trackNumbers[] = [
                            'number' => $tracking->getData('tracking_number'),
                            'url' => $trackingUrl . $tracking->getData('tracking_number')
                        ];
                    }
                }
            }
        }

        return $trackNumbers;
    }
}
