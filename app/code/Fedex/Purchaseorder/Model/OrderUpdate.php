<?php

namespace Fedex\Purchaseorder\Model;

use Magento\Sales\Model\Order;
use Magento\Quote\Model\Quote;
use \Fedex\Purchaseorder\Api\OrderUpdateInterface;
use Psr\Log\LoggerInterface;

class OrderUpdate implements OrderUpdateInterface
{

    public function __construct(
        private \Magento\Framework\App\RequestInterface $request,
        private \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        private Order $order,
        private \Magento\Sales\Model\ResourceModel\Order\Status\Collection $statusCollection,
        private Order\Shipment\TrackFactory $trackFactory,
        private Order\ShipmentRepository $shipmentRepository,
        private \Magento\Sales\Model\Convert\Order $convertOrder,
        private \Magento\Sales\Model\OrderFactory $orderFactory,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * @param string $orderId
     *
     * @return array
     */
    public function updateOrderStatus($orderId)
    {
        $requestData=$this->request->getContent();
        $logdata = 'Magento Order Id = '.$orderId. ' Web Hook Request = '. $requestData;
        $this->logger->debug(__METHOD__ . ':' . __LINE__ . ' ' . $logdata);
        $requestData = json_decode($requestData,true);

        $orderStatusList = $this->statusCollection->toOptionArray();
        $orderStateStatusList = $this->statusCollection->joinStates()->getData();
        $responseMessage = false;
        if (!empty($orderId) && !empty($requestData))
        {
           $orderGTN=$requestData['orderNumber'];
           $orderStatus=$requestData['orderStatus'];
           $trackingNumberArray=$requestData['trackingNumbers'];
           if (!empty($orderStatus)) {
               $orderStatus=strtolower(trim($orderStatus));
               foreach ($orderStateStatusList as $val)
               {
                   $dbStatus=strtolower(trim($val['status']));
                   if ($dbStatus==$orderStatus) {
                      $responseMessage=$this->updateStatus($val['status'],$val['state'],$orderId,$trackingNumberArray);
                      break;
                   } elseif ($orderStatus=='cancelled' && $dbStatus=='canceled') {
                       $responseMessage=$this->updateStatus($val['status'],$val['state'],$orderId,$trackingNumberArray);
                       break;
                   } elseif ($dbStatus!=$orderStatus) {
                       $this->logger->error(__METHOD__ . ':' . __LINE__ . ' NOT A VALID STATUS');
                       $responseMessage=['code'=>'400','message'=>'Not a valid status'];
                   }
               }
           }
           return $responseMessage;
        }

    }

    /**
     * @param string $orderId,$state,$orderId
     * @param array $trackingId
     * @return array
     */
    public function updateStatus($status,$state,$orderId,$trackingId)
    {
        try {
            $orderObj = $this->orderRepository->get($orderId);
            $orderIncrementId = $orderObj->getIncrementId();
            $orderdata = $this->order->loadByIncrementId($orderIncrementId);
            //D-88390
            if (isset($trackingId) && isset($trackingId[0]) ) {
                $this->updateOrderTrackingId($orderId,$trackingId);
            }
            $orderdata->setState($state)->setStatus($status);
            $orderdata->save();
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' SUCCESS');
            return ['code'=>'200','message'=>'success'];
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            return ['code'=>'400','message'=>$e->getMessage()];
        }

    }
    /**
     *
     * @param string $orderId
     * @param array $trackingId
     *
     * @return void
     */
    public function updateOrderTrackingId($orderId, $trackingId)
    {

        $order = $this->orderRepository->get($orderId);
        $trackingId = is_array($trackingId)?$trackingId[0]:$trackingId;
        $shipmentId = $this->getShipmentId($orderId);

        if (!count($shipmentId)) {

            $orderShipment = $this->convertOrder->toShipment($order);

            foreach ($order->getAllItems() as $orderItem) {

                 if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                    continue;
                 }

                 $qty = $orderItem->getQtyToShip();
                 $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem)->setQty($qty);
                 $orderShipment->addItem($shipmentItem);
            }

            $orderShipment->register();
            $orderShipment->getOrder()->setIsInProcess(true);

            try {

                 $orderShipment->save();
                 $orderShipment->getOrder()->save();
                 $orderShipment->save();

                 $shipmentId = $this->getShipmentId($orderId);
                 $this->addTrackId($shipmentId, $trackingId);

            } catch (\Exception $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
                throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
                );
            }
        } else {

            $this->addTrackId($shipmentId, $trackingId);
        }
    }
    /**
     * get shipment id
     *
     * @param  string $orderId
     * @return array
     */
    public function getShipmentId($orderId)
    {
        $order = $this->orderFactory->create()->load($orderId);
        $shipmentCollection = $order->getShipmentsCollection();
        $shipmentId = [];
        foreach ($shipmentCollection as $shipment) {
            $shipmentId[] = $shipment->getId();
        }

        return $shipmentId;
    }


    /**
     * @param array $shipmentId
     * @param array $trackingId
     *
     * @return void
     */
    public function addTrackId($shipmentId, $trackingId)
    {
        $shipmentId = $shipmentId[0];
        $shipmmentTrackId = $this->getTracking($shipmentId);
        $number = $trackingId;
        $carrier = 'fedex';
        $title = 'Federal Express';

        if ($shipmmentTrackId == null) {

            try {

                $shipment = $this->shipmentRepository->get($shipmentId);

                $track = $this->trackFactory->create();
                $track->setNumber($number);
                $track->setCarrierCode($carrier);
                $track->setTitle($title);

                $shipment->addTrack($track);
                $this->shipmentRepository->save($shipment);

            } catch (NoSuchEntityException $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            }
        } else {

            $trackData = $this->trackFactory->create()->load($shipmmentTrackId);
            $trackData->setNumber($number);
            $trackData->save();
        }
    }


    /**
     * Get Shipment Trackig data by Shipment Id
     *
     * @param $shipmentId
     *  @codeCoverageIgnore
     * @return ShipmentTrackInterface|null
     */
    public function getTracking($shipmentId)
    {
        $shipment = $this->getShipmentById($shipmentId);

        $trackId = null;
        if ($shipment) {
            $shipmentTrack = $shipment->getTracks();
            $i = 0;
            foreach ($shipmentTrack as $shipTrack) {
                 if ($i === 0) {
                    $trackId = $shipTrack->getData('entity_id');
                    break;
                 }
                 $i++;
            }
            return $trackId;
        }
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Tracking id, ' . $trackId . ', is null');
        return null;
    }

    /**
     * Get Shipment data by Shipment Id
     *
     * @param $shipmentId
     *
     * @return ShipmentInterface|null
     */
    public function getShipmentById($shipmentId)
    {
        try {
            $shipment = $this->shipmentRepository->get($shipmentId);
        } catch (Exception $exception) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $exception->getMessage());
            $shipment = null;
        }
        return $shipment;
    }
}
