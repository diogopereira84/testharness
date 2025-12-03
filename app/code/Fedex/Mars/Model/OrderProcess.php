<?php
/**
 * @category    Fedex
 * @package     Fedex_Mars
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Olimjon Akhmedov <olimjon.akhmedov.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\Mars\Model;

use Exception;
use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\Mars\Api\OrderProcessInterface;
use Fedex\ProductBundle\Model\Config;
use Fedex\Shipment\Model\ProducingAddressFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection as CreditmemoCollection;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection as InvoiceCollection;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection as ShipmentCollection;
use Psr\Log\LoggerInterface;

class OrderProcess implements OrderProcessInterface
{
    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private ProducingAddressFactory $producingAddressFactory,
        private CompanyHelper $companyHelper,
        protected LoggerInterface $logger,
        private Config $config
    ) {
    }

    /**
     * Get order json
     *
     * @param int $id
     *
     * @return array
     */
    public function getOrderJson(int $id): array
    {
        $salesOrderData = [];

        try {
            $order = $this->orderRepository->get($id);
            $salesOrderData = $this->getSalesOrderData($order);

            $company = $order->getCustomerId() ? $this->companyHelper->getCompanyFromCustomerId($order->getCustomerId()) : null;
            if($company) {
                $salesOrderData['company'] = [
                    'id' => $company->getId(),
                    'name' => $company->getCompanyName()
                ];
            }else{
                $salesOrderData['company'] = null;
            }
            $salesOrderData['content_type'] = 'ORDER';
            $salesOrderData['order_producing_address'] = $this->getProducingAddress($id);
            $salesOrderData['sales_invoices'] = $this->getSalesInvoiceTableData($order->getInvoiceCollection());
            $salesOrderData['sales_order_addresses'] = $this->getSimpleOrderTableData($order->getAddressesCollection());
            $salesOrderData['sales_order_items'] = $this->getSimpleOrderTableData(
                $order->getItemsCollection(),
                needsEncoding: true,
                encodedValue: 'product_options'
            );
            $salesOrderData['sales_order_payments'] = $this->getSimpleOrderTableData(
                $order->getPaymentsCollection(),
                needsEncoding: true,
                encodedValue: 'additional_information'
            );
            $salesOrderData['sales_order_status_history'] = $this->getSimpleOrderTableData(
                $order->getStatusHistoryCollection(),
                'Fedex\Mars\Model\OrderProcess::salesOrderStatusHistoryFilter',
                ARRAY_FILTER_USE_BOTH
            );
            $salesOrderData['sales_shipments'] = $this->getSalesShipmentTableData(
                $order->getShipmentsCollection(),
                'packages'
            );
            $salesOrderData['sales_credit_memos'] = $this->getCreditMemoTableData($order->getCreditmemosCollection());

            return [$salesOrderData];
        } catch (NoSuchEntityException $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ . ' Unable to find order id to build MARS order json. Order ID = ' .
                $id . ' . Error Message: ' . $e->getMessage()
            );
        } catch (Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ .' An error occurred while building MARS order json. Order ID = ' .
                $id . ' . Error Message: ' . $e->getMessage()
            );
        }

        return $salesOrderData;
    }

    /**
     * Gets correct order data from sales_order_address, sales_order_item, sales_order_payment,
     * sales_order_status_history, sales_shipment_item, sales_shipment_track, sales_creditmemo_item and
     * sales_invoice_item tables
     *
     * @param mixed $orderCollection
     * @param callable $orderFilterFn
     * @param int $orderFilterMode
     * @param bool $needsEncoding
     * @param string $encodedValue
     *
     * @return array
     */
    public function getSimpleOrderTableData(
        $orderCollection,
        $orderFilterFn = 'Fedex\Mars\Model\OrderProcess::checkIsNull',
        $orderFilterMode = 0,
        $needsEncoding = false,
        $encodedValue = ''
    )
    {
        $orderCollectionData = $parentItemsCollection = [];

        foreach ($orderCollection->getItems() as $item) {
            $orderItemData = array_filter($item->getData(), $orderFilterFn, $orderFilterMode);
            if ($needsEncoding) {
                if (isset($orderItemData[$encodedValue])) {
                    $orderItemData[$encodedValue] = json_encode($orderItemData[$encodedValue]);
                }
            }

            /**
             * Adding parent order item info to child order item
             */
            if ($this->isParentOrderItem($encodedValue, $item->getData())) {
                $parentItemsCollection[] = [
                    'parent_id' => (int) $item['item_id'],
                    'parent_name' => $item['name'],
                    'parent_type' => $item['product_type']
                ];
            }

            if ($this->isChildOrderItem($encodedValue, $item->getData())) {
                $parentInfo = $this->filterParentItems($parentItemsCollection, (int) $item['parent_item_id']);
                if ($parentInfo) {
                    $orderItemData = array_merge($orderItemData, $parentInfo);
                }
            }

            $orderCollectionData[] = $orderItemData;
        }

        return $orderCollectionData;
    }

    /**
     * Gets correct order data from sales_shipment table
     *
     * @param ShipmentCollection $shipmentCollection
     * @param string $encodedValue
     * @param callable $orderFilterFn
     *
     * @return array
     */
    public function getSalesShipmentTableData(
        $shipmentCollection,
        $encodedValue,
        $orderFilterFn = 'Fedex\Mars\Model\OrderProcess::checkIsNull'
    ) {
        $shipmentCollectionData = [];

        foreach ($shipmentCollection->getItems() as $shipmentItem) {
            $shipmentItemData = array_filter($shipmentItem->getData(), $orderFilterFn);
            if (isset($shipmentItemData[$encodedValue])) {
                $shipmentItemData[$encodedValue] = json_encode($shipmentItemData[$encodedValue]);
            }

            $salesShipmentItemCollection = [];
            foreach ($shipmentItem->getItemsCollection() as $salesShipmentItem) {
                $salesShipmentItemData = array_filter($salesShipmentItem->getData(), $orderFilterFn);
                $salesShipmentItemCollection[] = $salesShipmentItemData;
            }

            $shipmentItemData['sales_shipment_items'] = $salesShipmentItemCollection;
            $shipmentItemData['sales_shipment_tracks'] = $this->getSimpleOrderTableData(
                $shipmentItem->getTracksCollection()
            );

            $shipmentCollectionData[] = $shipmentItemData;
        }

        return $shipmentCollectionData;
    }

    /**
     * Gets correct order data from sales_creditmemo table
     *
     * @param CreditmemoCollection $creditMemoCollection
     * @param callable $orderFilterFn
     *
     * @return array
     */
    public function getCreditMemoTableData(
        $creditMemoCollection,
        $orderFilterFn = 'Fedex\Mars\Model\OrderProcess::checkIsNull'
    ) {
        $creditMemoCollectionData = [];

        foreach ($creditMemoCollection->getItems() as $creditMemoItem) {
            $creditMemoItemData = array_filter($creditMemoItem->getData(), $orderFilterFn);

            $creditMemoItemData['sales_credit_memo_items'] = $this->getSimpleOrderTableData(
                $creditMemoItem->getItemsCollection()
            );

            $creditMemoCollectionData[] = $creditMemoItemData;
        }

        return $creditMemoCollectionData;
    }

    /**
     * Gets correct order data from sales_invoice table
     *
     * @param InvoiceCollection $salesInvoiceCollection
     * @param callable $orderFilterFn
     *
     * @return array
     */
    public function getSalesInvoiceTableData(
        $salesInvoiceCollection,
        $orderFilterFn = 'Fedex\Mars\Model\OrderProcess::checkIsNull'
    ) {
        $salesInvoiceCollectionData = [];

        foreach ($salesInvoiceCollection->getItems() as $salesInvoiceItem) {
            $salesInvoiceItemData = array_filter($salesInvoiceItem->getData(), $orderFilterFn);

            $salesInvoiceItemData['sales_invoice_items'] = $this->getSimpleOrderTableData(
                $salesInvoiceItem->getItemsCollection(),
                'Fedex\Mars\Model\OrderProcess::salesInvoiceItemFilter',
                ARRAY_FILTER_USE_BOTH
            );

            $salesInvoiceCollectionData[] = $salesInvoiceItemData;
        }

        return $salesInvoiceCollectionData;
    }

    /**
     * Gets correct order data from sales_order table
     *
     * @param OrderInterface $salesOrder
     *
     * @return array
     */
    public function getSalesOrderData($salesOrder)
    {
        return array_filter(
            $salesOrder->getData(),
            'Fedex\Mars\Model\OrderProcess::salesOrderFilter',
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * Get Producing Address
     *
     * @param int $id
     * @return array
     */
    public function getProducingAddress(int $id): array
    {
        $data = [];
        $producingAddress = $this->producingAddressFactory->create()->getCollection()
            ->addFieldToFilter('order_id', $id)
            ->addFieldToSelect(['address', 'phone_number', 'email_address', 'location_id', 'additional_data'])
            ->load()->getFirstItem();

        $data['address'] = $producingAddress->getAddress();
        $data['phone_number'] = $producingAddress->getPhoneNumber();
        $data['email_address'] = $producingAddress->getEmailAddress();
        $data['location_id'] = $producingAddress->getLocationId();
        $data['additional_data'] = $producingAddress->getAdditionalData();

        return $data;
    }

    /**
     * Array filter function that will omit null values from array
     *
     * @codeCoverageIgnore
     *
     * @param mixed $entry
     *
     * @return bool
     */
    protected function checkIsNull($entry)
    {
        return $entry !== null;
    }

    /**
     * Array filter function that will return required sales_invoice_item data
     *
     * @codeCoverageIgnore
     *
     * @param mixed $v
     * @param mixed $k
     *
     * @return bool
     */
    protected function salesInvoiceItemFilter($v, $k)
    {
        return $v !== null && $k != 'invoice';
    }

    /**
     * Array filter function that will return required sales_order data
     *
     * @codeCoverageIgnore
     *
     * @param mixed $v
     * @param mixed $k
     *
     * @return bool
     */
    protected function salesOrderFilter($v, $k)
    {
        return $v !== null && $k != 'items' && $k != 'extension_attributes' && $k != 'payment' &&
            $k != 'customer_contact_number' && $k != 'customer_external_identifier';
    }

    /**
     * Array filter function that will return required sales_order_status_history data
     *
     * @codeCoverageIgnore
     *
     * @param mixed $v
     * @param mixed $k
     *
     * @return bool
     */
    protected function salesOrderStatusHistoryFilter($v, $k)
    {
        return $v !== null && $k != 'store_id';
    }

    /**
     * Check if order item is a parent order item (bundle / configurable)
     *
     * @param string $encodedValue
     * @param array $item
     * @return bool
     */
    private function isParentOrderItem(string $encodedValue, array $item): bool
    {
        return $this->isBundleToggleEnabled()
            && $encodedValue === 'product_options'
            && !$item['parent_item_id'];
    }

    /**
     * Check if order item is a child order item (simple)
     *
     * @param string $encodedValue
     * @param array $item
     * @return bool
     */
    private function isChildOrderItem(string $encodedValue, array $item): bool
    {
        return $this->isBundleToggleEnabled()
            && $encodedValue === 'product_options'
            && $item['parent_item_id'];
    }

    /**
     * Filter array to find parent order item of a child
     *
     * @param array $parentItems
     * @param int $parentId
     * @return array
     */
    private function filterParentItems(array $parentItems, int $parentId): array
    {
        foreach ($parentItems as $parentItem) {
            if ($parentItem['parent_id'] === $parentId) {
                return [
                    'parent_product_name' => $parentItem['parent_name'],
                    'parent_product_type' => $parentItem['parent_type']
                ];
            }
        }
        return [];
    }

    /**
     * @return bool
     */
    private function isBundleToggleEnabled():bool
    {
        return $this->config->isTigerE468338ToggleEnabled();
    }
}
