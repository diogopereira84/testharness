<?php
declare(strict_types=1);

namespace Fedex\OrderGraphQl\Model\Resolver;

use Exception;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceProduct\Api\ShopRepositoryInterface;
use Fedex\OrderGraphQl\Model\Resolver\DataProvider\OrderStatusMapping;
use Fedex\OrderGraphQl\Model\Resolver\DataProvider\ShipmentStatusLabel;
use Fedex\OrderGraphQl\Model\Resolver\OrderSearchRequest\JobSummariesOrderData;
use Fedex\OrderGraphQl\Model\Resolver\OrderSearchRequest\OrderSearchRequestHelper;
use Fedex\OrderGraphQl\Model\Resolver\OrderSearchRequest\RecipientSummariesData;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Framework\GraphQl\Query\Resolver\BatchResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Sales\Model\Order;
use Fedex\Cart\Api\CartIntegrationItemRepositoryInterface;
use Magento\Sales\Model\Order\Item;
use Psr\Log\LoggerInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Fedex\OrderGraphQl\Api\GetPoliticalDisclosureInformationInterface;

/**
 * TODO: refactor lengthy methods
 */
class OrderSearchRequest implements BatchResolverInterface
{
    /**
     * string SOURCE
     */
    private const SOURCE = 'FJMP';

    protected const INSTORE_ORDER_SEARCH_TOGGLE_ENABLED_CONFIG = 'instore_order_search_toggle';

    /**
     * @param DataProvider\OrderSearchRequest $orderSearchRequestProvider
     * @param ShipmentStatusLabel $shipmentStatusLabelProvider
     * @param OrderStatusMapping $orderStatusMapping
     * @param CartIntegrationItemRepositoryInterface $cartIntegrationRepository
     * @param LoggerInterface $logger
     * @param ShopRepositoryInterface $shopRepository
     * @param JobSummariesOrderData $jobSummariesOrderData
     * @param RecipientSummariesData $recipientSummariesData
     * @param OrderSearchRequestHelper $orderSearchRequestHelper
     * @param BatchResponseFactory $batchResponseFactory
     * @param ToggleConfig $toggleConfig
     * @param GetPoliticalDisclosureInformationInterface $getPoliticalDisclosureInformation
     * @param InstoreConfig $instoreConfig
     */
    public function __construct(
        protected DataProvider\OrderSearchRequest               $orderSearchRequestProvider,
        protected DataProvider\ShipmentStatusLabel              $shipmentStatusLabelProvider,
        protected OrderStatusMapping                            $orderStatusMapping,
        private readonly CartIntegrationItemRepositoryInterface $cartIntegrationRepository,
        private readonly LoggerInterface                        $logger,
        private readonly ShopRepositoryInterface                $shopRepository,
        private readonly JobSummariesOrderData                  $jobSummariesOrderData,
        private readonly RecipientSummariesData                 $recipientSummariesData,
        private readonly OrderSearchRequestHelper               $orderSearchRequestHelper,
        private readonly BatchResponseFactory                   $batchResponseFactory,
        private readonly ToggleConfig                           $toggleConfig,
        private readonly GetPoliticalDisclosureInformationInterface $getPoliticalDisclosureInformation,
        private readonly InstoreConfig                              $instoreConfig
    ) {
    }

    /**
     * @param $context
     * @param Field $field
     * @param array $requests
     * @return BatchResponse
     * @throws GraphQlAuthenticationException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     */
    public function resolve(
        $context,
        Field $field,
        array $requests
    ): BatchResponse {
        $response = $this->batchResponseFactory->create();
        $data = [];
        try {
            if ($this->toggleConfig->getToggleConfigValue(self::INSTORE_ORDER_SEARCH_TOGGLE_ENABLED_CONFIG)) {
                foreach ($requests as $request) {
                    $args = $request->getArgs();
                    $results = $this->orderSearchRequestProvider->orderSearchRequest($args);
                    $orders = $results['orders'];
                    $isPartial = $results['partial'];
                    $orderSummaries = $this->getOrderSummaries($orders);
                    $response->addResponse($request, [
                        'partialResults' => $isPartial,
                        'orderSummaries' => $orderSummaries
                    ]);
                }
            } else {
                $response->addResponse(end($requests), $data);
            }
            return $response;
        } catch (Exception $exception) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $exception->getMessage());
            throw new GraphQlAuthenticationException(__($exception->getMessage()));
        }
    }

    /**
     * @param array $orders
     * @return array
     * @throws NoSuchEntityException
     */
    protected function getOrderSummaries(array $orders): array
    {
        $orderSummaries = [];
        foreach ($orders as $order) {
            /**
             * @var Order $order
             * @var Order\Address $shippingAddress
             */
            $orderIncrementId = $order->getIncrementId();
            $shippingAddress = $order->getShippingAddress();
            $orderItems = $order->getItems();
            $quoteItemsInstanceId = $this->getQuoteItemsInstanceId($orderItems);
            $currencyCode = $order->getOrderCurrency()->getCurrencyCode();
            $productAssociationsData = $this->getProductAssociationsData($orderItems, $currencyCode, $quoteItemsInstanceId);
            $recipients = $this->recipientSummariesData->getData($quoteItemsInstanceId, $order);
            $jobSummaries = $this->jobSummariesOrderData->getData(
                $productAssociationsData,
                $quoteItemsInstanceId,
                $order
            );

            if ($this->instoreConfig->isEnablePoliticalDisclosureInOrderSearch()) {
                $politicalDisclosureInformation = $this->getPoliticalDisclosureInformation->getPoliticalDisclosureInfo($order);
            } else {
                $politicalDisclosureInformation = null;
            }

            $orderSummaries[] = [
                'orderNumber' => $order->getIncrementId(),
                'status' => $this->orderStatusMapping->getMappingKey($order->getStatus()),
                'currency' => $order->getOrderCurrency()->getCurrencyCode(),
                'totalAmount' => $order->getGrandTotal(),
                'submissionTime' => $this->orderSearchRequestHelper->getFormattedCstDate($order->getCreatedAt()),
                'orderContact' => [
                    'contact' => [
                        'personName' => [
                            'firstName' => $order->getCustomerFirstname(),
                            'lastName' => $order->getCustomerLastname()
                        ],
                        'emailDetail' => [
                            'emailAddress' => $order->getCustomerEmail()
                        ],
                        'phoneNumberDetails' => [[
                            'phoneNumber' => [
                                'number' => $shippingAddress->getTelephone()
                            ],
                            'usage' => 'PRIMARY'
                        ]]
                    ]
                ],
                'productSummaries' => $this->getProductSummaries($orderItems, $quoteItemsInstanceId),
                'recipientSummaries' => $recipients,
                'productDetails' => $this->getProductDetails($orderItems, $orderIncrementId, $quoteItemsInstanceId),
                'recipients' => $recipients,
                'jobSummaries' => $jobSummaries,
                'transactionReference' => $this->getTransactionReference($order),
                'compliance' => [
                    'washingtonPolitical' => $politicalDisclosureInformation
                ]
            ];
        }
        return $orderSummaries;
    }

    /**
     * @param $orderItems
     * @param array $quoteItemsInstanceId
     * @return array
     */
    private function getProductSummaries($orderItems, array $quoteItemsInstanceId): array
    {
        $productSummaries = [];
        foreach ($orderItems as $orderItem) {
            /**
             * @var Item $orderItem
             */
            $buyRequest = $orderItem->getBuyRequest();
            $productName = $orderItem->getName();
            $userProductName = $orderItem->getName();
            if (!empty($buyRequest->getData('external_prod')[0])) {
                $product = $buyRequest->getData('external_prod')[0];
                if ($orderItem->getMiraklOfferId()) {
                    $product = $product['product'];
                }
                $productName = $product['name'];
                $userProductName = array_key_exists('userProductName', $product) ?
                    $product['userProductName'] :
                    $product['name'];
            }
            if (!empty($buyRequest->getData('fileManagementState')['projects'])) {
                $projects = $buyRequest->getData('fileManagementState')['projects'][0];
                $userProductName = array_key_exists('projectName', $projects) ?
                    $projects['projectName'] : $orderItem->getName();
            }
            $productSummaries[] = [
                'id' => $quoteItemsInstanceId[$orderItem->getId()],
                'name' => $productName,
                'userProductName' => $userProductName
            ];
        }
        return $productSummaries;
    }

    /**
     * @param OrderItemInterface[] $orderItems
     * @param string $orderIncrementId
     * @param array $quoteItemsInstanceId
     * @return array
     */
    private function getProductDetails(array $orderItems, string $orderIncrementId, array $quoteItemsInstanceId): array
    {
        $products = [];
        foreach ($orderItems as $orderItem) {
            try {
                if ($orderItem->getMiraklOfferId()) {
                    $products = array_merge($products, $this->getMiraklProductData($orderItem));
                    continue;
                }
                if (!$this->orderSearchRequestHelper->checkInstore((string)$orderIncrementId)) {
                    $products = array_merge($products, $this->getDefaultProductData($orderItem, $quoteItemsInstanceId));
                    continue;
                }
                $products[] = $this->getInstoreProductData($orderItem, $quoteItemsInstanceId);
            } catch (NoSuchEntityException) {
                continue;
            }
        }
        return $products;
    }

    /**
     * @param OrderItemInterface $orderItem
     * @return array
     */
    private function getMiraklProductData(OrderItemInterface $orderItem): array
    {
        $productOptions = $orderItem->getProductOptions();
        $optionData = $productOptions['info_buyRequest'] ?? null;

        $products = [];
        if ($optionData && isset($optionData['external_prod'])) {
            for ($i = 0; $i < sizeof($optionData['external_prod']); $i++) {
                $externalProduct = $optionData['external_prod'][$i];
                $products[$i]['product'] = $externalProduct['product'] ?? null;
                $externalSkus = $externalProduct['externalSkus'] ?? [];
                foreach ($externalSkus as $externalSku) {
                    $products[$i]['product']['externalSkus'][] = $externalSku;
                }
            }
        }
        return $products;
    }

    /**
     * @param OrderItemInterface $orderItem
     * @param array $quoteItemsInstanceId
     * @return array
     */
    private function getInstoreProductData(OrderItemInterface $orderItem, array $quoteItemsInstanceId): array
    {
        $productJson = json_decode(
            $this->cartIntegrationRepository->getByQuoteItemId(
                (int) $orderItem->getQuoteItemId()
            )->getItemData() ?? '{}',
            true
        );

        if (isset($productJson["fxoProductInstance"]["productConfig"]["product"]["instanceId"])) {
            $productJson["fxoProductInstance"]["productConfig"]["product"]["instanceId"] =
                $quoteItemsInstanceId[$orderItem->getId()];
        }

        return [
            'product' => $productJson["fxoProductInstance"]["productConfig"]["product"],
        ];
    }

    /**
     * @param $orderItems
     * @param $currencyCode
     * @param array $quoteItemsInstanceId
     * @return array
     * @throws NoSuchEntityException
     */
    protected function getProductAssociationsData($orderItems, $currencyCode, array $quoteItemsInstanceId): array
    {
        $productAssociations = [];
        foreach ($orderItems as $orderItem) {
            $miraklShop = null;
            if ($miraklShopId = $orderItem->getData('mirakl_shop_id')) {
                $miraklShop = $this->shopRepository->getById((int) $miraklShopId);
            }

            $productAssociations[$orderItem->getSku()] = [
                'id' => $quoteItemsInstanceId[$orderItem->getId()],
                'currency' => $currencyCode,
                'reference' => null,
                'binLocation' => null,
                'seller' =>  isset($miraklShop) ? $miraklShop->getName() : 'FXO'
            ];
        }
        return $productAssociations;
    }

    /**
     * @param $order
     * @return array
     */
    private function getTransactionReference($order): array
    {
        $retailTransactionId = $order->getPayment()->getRetailTransactionId() ?? null;
        return [
          'reference' => $retailTransactionId,
          'source' => $retailTransactionId ? self::SOURCE : null
        ];
    }

    /**
     * @param $orderItem
     * @return array
     */
    private function getDefaultProductData($orderItem, array $quoteItemsInstanceId): array
    {
        $productOptions = $orderItem->getProductOptions();
        $optionData = $productOptions['info_buyRequest'] ?? null;
        $products = [];
        if ($optionData) {
            for ($i = 0; $i < sizeof($optionData['external_prod']); $i++) {
                $externalProduct = $optionData['external_prod'][$i];
                $products[$i]['product'] = $externalProduct ?? null;
                $products[$i]['product']['instanceId'] = $quoteItemsInstanceId[$orderItem->getId()];
                $externalSkus = $externalProduct['externalSkus'] ?? [];
                foreach ($externalSkus as $externalSku) {
                    $products[$i]['product']['externalSkus'][] = $externalSku;
                }
            }
        }
        return $products;
    }

    /**
     * @param array|null $orderItems
     * @return array
     */
    private function getQuoteItemsInstanceId(array|null $orderItems): array
    {
        $instanceIds = [];
        foreach ($orderItems as $item) {
            $instanceIds[$item->getId()] = $item->getQuoteItemId();
        }
        return $instanceIds;
    }
}
