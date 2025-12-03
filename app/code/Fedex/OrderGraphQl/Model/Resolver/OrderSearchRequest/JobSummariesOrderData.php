<?php
/**
 * @category     Fedex
 * @package      Fedex_OrderGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Yash Rajeshbhai solanki
 */
declare(strict_types=1);

namespace Fedex\OrderGraphQl\Model\Resolver\OrderSearchRequest;

use Fedex\OrderGraphQl\Model\Resolver\DataProvider\ShipmentStatusLabel;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;

class JobSummariesOrderData
{
    /**
     * @param ShipmentStatusLabel $shipmentStatusLabelProvider
     * @param OrderSearchRequestHelper $orderSearchRequestHelper
     */
    public function __construct(
        protected ShipmentStatusLabel $shipmentStatusLabelProvider,
        private readonly OrderSearchRequestHelper $orderSearchRequestHelper
    ) {
    }

    /**
     * @param $productAssociationsData
     * @param array $quoteItemsInstanceId
     * @param OrderInterface $order
     * @return array
     */
    public function getData(
        $productAssociationsData,
        array $quoteItemsInstanceId,
        OrderInterface $order
    ): array
    {
        $shipments = $order->getShipmentsCollection();
        $orderCurrency = $order->getOrderCurrency()->getCurrencyCode();

        $jobSummaries = $this->getShipmentJobSummaries(
            $shipments,
            $productAssociationsData,
            $orderCurrency,
            $quoteItemsInstanceId
        );

        $missingShipmentsItems = $this->orderSearchRequestHelper->getItemsWithoutShipment($order);
        foreach ($missingShipmentsItems as $items) {
            $jobSummaries = array_merge(
                $this->getJobSummariesForNonShipmentItem(
                    $items,
                    $productAssociationsData,
                    $orderCurrency,
                    $quoteItemsInstanceId
                ),
                $jobSummaries
            );
        }
        return $jobSummaries;
    }

    /**
     * @param $shipments
     * @param $productAssociationsData
     * @param $orderCurrency
     * @param array $quoteItemsInstanceId
     * @return array
     */
    private function getShipmentJobSummaries(
        $shipments,
        $productAssociationsData,
        $orderCurrency,
        array $quoteItemsInstanceId
    ): array {
        $jobSummaries = [];
        foreach ($shipments as $shipment) {
            /**
             * @var Order\Shipment $shipment
             */
            $trackingNumbers = [];
            foreach ($shipment->getTracksCollection()->getItems() as $track) {
                /**
                 * @var Order\Shipment\Track $track
                 */
                $trackingNumbers[] = $track->getTrackNumber();
            }

            $productAssociations = [];
            $shipmentStatusLabel = $this->shipmentStatusLabelProvider->getShipmentLabel(
                $shipment->getShipmentStatus() ? (int) $shipment->getShipmentStatus() : 1
            );
            $seller = null;
            $shipmentNetAmount = 0;
            foreach ($shipment->getItems() as $item) {
                $associationData = $productAssociationsData[$item->getSku()];
                $productAssociations[] = [
                    'id' => $quoteItemsInstanceId[$item->getOrderItemId()],
                    'quantity' => $this->orderSearchRequestHelper->getQuantity($item),
                    'currency' => $associationData['currency'],
                    'netAmount' => $item->getPrice() * $this->orderSearchRequestHelper->getQuantity($item),
                    'reference' => $associationData['reference'],
                    'binLocation' => $associationData['binLocation'],
                    'status' => $shipmentStatusLabel
                ];
                $shipmentNetAmount += ($item->getPrice() * $this->orderSearchRequestHelper->getQuantity($item));
                if (!$seller) {
                    $seller = $associationData['seller'];
                }
            }
            $jobSummaries[] = [
                'jobGTN' => null,
                'parentGTN' => null,
                'location' => [
                    'id' => null
                ],
                'status' => $shipmentStatusLabel,
                'seller' => $seller,
                'recipientReferences' => [
                    [
                        'reference' => $shipment->getData('fxo_shipment_id'),
                        'trackingNumbers' => $trackingNumbers,
                        'productAssociations' => $productAssociations
                    ]
                ],
                'currency' => $orderCurrency,
                'netAmount' => $shipmentNetAmount,
                'productionDueTime' => $shipment->getData('shipping_due_date') ?
                    $this->orderSearchRequestHelper->getFormattedCstDate($shipment->getData('shipping_due_date')) :
                    null
            ];
        }

        return $jobSummaries;
    }

    /**
     * @param $orderItems
     * @param $productAssociationsData
     * @param $orderCurrency
     * @param $quoteItemsInstanceId
     * @return array
     */
    private function getJobSummariesForNonShipmentItem(
        $orderItems,
        $productAssociationsData,
        $orderCurrency,
        $quoteItemsInstanceId
    ): array {
        $jobSummaries = [];
        $shipmentNetAmount = 0;
        $productAssociations = [];
        $seller = null;
        $shipmentStatusLabel = $this->shipmentStatusLabelProvider->getShipmentLabel(1);
        foreach ($orderItems as $item) {
            $associationData = $productAssociationsData[$item->getSku()];
            $productAssociations[] = [
                'id' => $quoteItemsInstanceId[$item->getId()],
                'quantity' => $item->getQtyOrdered(),
                'currency' => $associationData['currency'],
                'netAmount' => $item->getPrice() * $item->getQtyOrdered(),
                'reference' => $associationData['reference'],
                'binLocation' => $associationData['binLocation'],
                'status' => $shipmentStatusLabel
            ];
            $shipmentNetAmount += ($item->getPrice() * $item->getQtyOrdered());
            if (!$seller) {
                $seller = $associationData['seller'];
            }
        }
        $jobSummaries[] = [
            'jobGTN' => null,
            'parentGTN' => null,
            'location' => [
                'id' => null
            ],
            'status' => $shipmentStatusLabel,
            'seller' => $seller,
            'recipientReferences' => [
                [
                    'reference' => null,
                    'trackingNumbers' => [],
                    'productAssociations' => $productAssociations
                ]
            ],
            'currency' => $orderCurrency,
            'netAmount' => $shipmentNetAmount,
            'productionDueTime' =>  null
        ];
        return $jobSummaries;
    }
}
