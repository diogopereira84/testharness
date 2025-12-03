<?php
/**
 * @category     Fedex
 * @package      Fedex_OrderGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Yash Rajeshbhai solanki
 */
declare(strict_types=1);

namespace Fedex\OrderGraphQl\Model\Resolver\OrderSearchRequest;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;

class RecipientSummariesData
{
    /**
     * @param OrderSearchRequestHelper $orderSearchRequestHelper
     */
    public function __construct(
        protected OrderSearchRequestHelper $orderSearchRequestHelper
    ) {
    }

    /**
     * @param array $quoteItemsInstanceId
     * @param OrderInterface $order
     * @return array
     */
    public function getData(
        array $quoteItemsInstanceId,
        OrderInterface $order
    ): array
    {
        $shipments = $order->getShipmentsCollection();
        $shippingAddress = $order->getShippingAddress();

        $shipmentRecipientSummaries = $this->getShipmentRecipientSummaries(
            $shipments,
            $quoteItemsInstanceId
        );

        $missingShipmentsItems = $this->orderSearchRequestHelper->getItemsWithoutShipment($order);
        foreach ($missingShipmentsItems as $items) {
            $shipmentRecipientSummaries = array_merge(
                $this->getRecipientSummariesForNonShipmentItem(
                    $quoteItemsInstanceId,
                    $items,
                    $shippingAddress
                ),
                $shipmentRecipientSummaries
            );
        }
        return $shipmentRecipientSummaries;
    }

    /**
     * @param $shipments
     * @param array $quoteItemsInstanceId
     * @return array
     */
    private function getShipmentRecipientSummaries($shipments, array $quoteItemsInstanceId): array
    {
        $recipientSummaries = [];
        foreach ($shipments as $shipment) {
            /**
             * @var Order\Shipment $shipment
             * @var Order\Address $shippingAddress
             */
            $shippingAddress = $shipment->getShippingAddress();
            $productAssociations = [];

            foreach ($shipment->getItems() as $item) {
                $productAssociations[] = [
                    'id' => $quoteItemsInstanceId[$item->getOrderItemId()],
                    'quantity' => $this->orderSearchRequestHelper->getQuantity($item)
                ];
            }

            $recipientSummaries[] = [
                'reference' => $shipment->getData('fxo_shipment_id'),
                'contact' => [
                    'personName' => [
                        'firstName' => $shippingAddress->getFirstname(),
                        'lastName' => $shippingAddress->getLastname()
                    ],
                    'company' => [
                        'name' => $shippingAddress->getCompany()
                    ],
                    'emailDetail' => [
                        'emailAddress' => $shippingAddress->getEmail()
                    ],
                    'phoneNumberDetails' => [[
                        'phoneNumber' => [
                            'number' => $shippingAddress->getTelephone()
                        ],
                        'usage' => 'PRIMARY'
                    ]]
                ],
                'productAssociations' => $productAssociations
            ];
        }

        return $recipientSummaries;
    }

    /**
     * @param $quoteItemsInstanceId
     * @param $orderItems
     * @param $shippingAddress
     * @return array
     */
    private function getRecipientSummariesForNonShipmentItem(
        $quoteItemsInstanceId,
        $orderItems,
        $shippingAddress
    ): array {
        $productAssociations = [];

        foreach ($orderItems as $item) {
            $productAssociations[] = [
                'id' => $quoteItemsInstanceId[$item->getId()],
                'quantity' => $item->getQtyOrdered()
            ];
        }

        $recipientSummaries[] = [
            'reference' => null,
            'contact' => [
                'personName' => [
                    'firstName' => $shippingAddress->getFirstname(),
                    'lastName' => $shippingAddress->getLastname()
                ],
                'company' => [
                    'name' => $shippingAddress->getCompany()
                ],
                'emailDetail' => [
                    'emailAddress' => $shippingAddress->getEmail()
                ],
                'phoneNumberDetails' => [[
                    'phoneNumber' => [
                        'number' => $shippingAddress->getTelephone()
                    ],
                    'usage' => 'PRIMARY'
                ]]
            ],
            'productAssociations' => $productAssociations
        ];

        return $recipientSummaries;
    }
}
