<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SubmitOrderSidebar\Helper;

use Exception;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Quote\Model\QuoteFactory;
use Fedex\Shipment\Model\ProducingAddressFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class SubmitOrderOptimizedHelper extends AbstractHelper
{
    public const ORDER_ID = 'order_id';
    public const ORDER_ID_TEXT = ': Order Id:';
    public const MESSAGE_TEXT = ' Message => ';
    public const DELIVERY_LINES = 'deliveryLines';
    public const ESTIMATED_DELIVERY_LOCAL_TIME = 'estimatedDeliveryLocalTime';
    public const ESTIMATED_DELIVERY_DURATION = 'estimatedDeliveryDuration';
    public const PHONE_NUMBER = 'phone_number';
    public const EMAIL_ADDRESS = 'email_address';
    public const ADDRESS = 'address';
    public const PHONE_NUMBER_DETAIL = 'phoneNumberDetails';
    public const PHONE_NUMBER_TEXT = 'phoneNumber';
    public const NUMBER_TEXT = 'number';
    public const ESTIMATED_TIME = 'estimated_time';
    public const OUTPUT = 'output';
    public const RATE_QUOTE_DETAILS = 'rateQuoteDetails';
    public const RATE_QUOTE = 'rateQuote';
    public const RESPONSIBLE_LOCATION_ID = 'responsibleLocationId';

    /**
     * SubmitOrderOptimizedHelper constructor
     *
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param QuoteFactory $quoteFactory
     * @param LoggerInterface $logger
     * @param ProducingAddressFactory $producingAddressFactory
     * @param ShipmentRepositoryInterface $shipmentRepositoryInterface
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        Context $context,
        protected CheckoutSession $checkoutSession,
        protected QuoteFactory $quoteFactory,
        protected LoggerInterface $logger,
        protected ProducingAddressFactory $producingAddressFactory,
        protected ShipmentRepositoryInterface $shipmentRepositoryInterface,
        protected SearchCriteriaBuilder $searchCriteriaBuilder,
        private ToggleConfig $toggleConfig
    ) {
        parent::__construct($context);
    }

    /**
     * Get Retail transaction id from transaction response
     *
     * @param string $transactionResponse
     * @return int|string
     */
    public function getRetailTransactionId($transactionResponse)
    {
        $retailTransactionId = null;
        $transactionResponseData = json_decode((string)$transactionResponse[0]);
        if (isset($transactionResponseData->output)) {
            $retailTracId = $transactionResponseData->output->checkout->transactionHeader;
            if (isset($retailTracId->retailTransactionId)) {
                $retailTransactionId = $retailTracId->retailTransactionId;
            }
        }
        return $retailTransactionId;
    }

    /**
     * Load quote object
     *
     * @param int $quoteId
     * @return object
     */
    public function getQuoteObject($quoteId)
    {
        return $this->quoteFactory->create()->load($quoteId);
    }

    /**
     * Unset orderinprogress from session
     *
     * @return mixed
     */
    public function unsetOrderInProgress()
    {
        return $this->checkoutSession->unsOrderInProgress();
    }

    /**
     * Is Alternate Contact
     *
     * @return bool|null
     */
    public function isAlternateContact()
    {
        return $this->checkoutSession->getAlternateContact() ?? null;
    }

    /**
     * Set Alternate Contact
     * @param boolean $alternateContactFlag
     */
    public function setAlternateContactFlag($alternateContactFlag)
    {
        if ($alternateContactFlag) {
            $this->checkoutSession->setAlternateContactAvailable(true);
        }
    }

    /**
     * Is Alternate Pickup Person
     *
     * @return bool|null
     */
    public function isAlternatePickupPerson()
    {
        return $this->checkoutSession->getAlternatePickupPerson() ?? null;
    }

    /**
     * Get Current Checkout Session Quote
     *
     * @return object
     */
    public function getCheckoutSessionQuote()
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     * Producing address
     *
     * @param array $rateQuoteDetails
     * @param object $order
     */
    public function producingAddress($rateQuoteDetails, $order)
    {
        $this->logger->info(
            __METHOD__ . ':' . __LINE__. ': '.'Before preparing producing address for Quote Id: '. $order->getQuoteId()
        );
        $this->prepareOrderProducingAddress($rateQuoteDetails, $order);
        $this->logger->info(
            __METHOD__ . ':' . __LINE__. ': '.' After prepared producing address for Quote Id:' . $order->getQuoteId()
        );
    }

    /**
     * Prepare Order Producing ddress
     *
     * @param array $rateQuoteDetails
     * @param object $order
     * @return void
     */
    public function prepareOrderProducingAddress($rateQuoteDetails, $order)
    {
        try {
            $addressInfo = [];
            $locationId = null;
            if (!empty($rateQuoteDetails)) {

                $dataForAddress = [];
                foreach ($rateQuoteDetails as $data) {
                    if (isset($data['supportContact'])) {
                        $dataForAddress = $data['supportContact'];
                    }
                    if (isset($data['deliveryLines'][0]['pickupDetails']['locationName'])) {
                        $locationId = $data['deliveryLines'][0]['pickupDetails']['locationName'];
                    }
                }
                $addressArray = $dataForAddress[self::ADDRESS];
                $addtionalData = $this->getAdditionalInfo($rateQuoteDetails);
                if ($addressArray) {
                    $phoneNumber = $emailAddress = '';
                    if (isset($dataForAddress[self::PHONE_NUMBER_DETAIL][self::PHONE_NUMBER_TEXT][self::NUMBER_TEXT])) {
                        $phoneNumber =
                        $dataForAddress[self::PHONE_NUMBER_DETAIL][self::PHONE_NUMBER_TEXT][self::NUMBER_TEXT];
                    }
                    if (isset($dataForAddress['email'])) {
                        $emailAddress = $dataForAddress['email'];
                    }
                    $addressInfo[self::PHONE_NUMBER] = $phoneNumber;
                    $addressInfo[self::EMAIL_ADDRESS] = $emailAddress;
                    $street = implode(" ", $addressArray['streetLines']);
                    $city = $addressArray['city'];
                    $stateOrProvinceCode = $addressArray['stateOrProvinceCode'];
                    $postalCode = $addressArray['postalCode'];
                    $countryCode = $addressArray['countryCode'];
                    $address = $street.' '.$city.' '.$stateOrProvinceCode.' '.$postalCode.' '.$countryCode;
                    $addressInfo[self::ADDRESS] = $address;
                }
                $this->saveOrderProducingAddress($addressInfo, $order, $addtionalData, $locationId);
            }
        } catch (Exception $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__. ': '. self::ORDER_ID_TEXT . $order->getId() . $e->getMessage()
            );
        }
    }

    /**
     * Get Additional Information
     *
     * @param array $rateQuoteDetails
     * @return array
     */
    public function getAdditionalInfo($rateQuoteDetails)
    {
        $estimatedTime = $estimatedDuration = $responsibleLocationId = null; 
        if (isset($rateQuoteDetails[0][self::DELIVERY_LINES][0][self::ESTIMATED_DELIVERY_LOCAL_TIME])
        && $rateQuoteDetails[0][self::DELIVERY_LINES][0][self::ESTIMATED_DELIVERY_LOCAL_TIME] != '') {
            $estimatedTime = $rateQuoteDetails[0][self::DELIVERY_LINES][0][self::ESTIMATED_DELIVERY_LOCAL_TIME];
        } else {
            if (isset($rateQuoteDetails[0][self::DELIVERY_LINES][0][self::ESTIMATED_DELIVERY_DURATION]['value'])) {
                $value = $rateQuoteDetails[0][self::DELIVERY_LINES][0][self::ESTIMATED_DELIVERY_DURATION]['value'];
                $unit = $rateQuoteDetails[0][self::DELIVERY_LINES][0][self::ESTIMATED_DELIVERY_DURATION]['unit'];
                $estimatedDuration = $value . ' ' . $unit;
            }
        }
            $responsibleLocationId = $this->getResponsibleLocationId($rateQuoteDetails);
            return [self::ESTIMATED_TIME => $estimatedTime, 'estimated_duration' => $estimatedDuration,
            'responsible_location_id' => $responsibleLocationId,
        ];
        
    }

    /**
     * Save Order Producing Address
     *
     * @param array $addressInfo
     * @param object $order
     * @param array $addtionalData
     * @param string $locationId
     * @return void
     */
    public function saveOrderProducingAddress($addressInfo, $order, $addtionalData, $locationId = null)
    {
        try {
            $address = $phoneNumber = $emailAddress = '';
            if ($addressInfo[self::ADDRESS]) {
                $address = $addressInfo[self::ADDRESS];
                $phoneNumber = $addressInfo[self::PHONE_NUMBER];
                $emailAddress = $addressInfo[self::EMAIL_ADDRESS];
            }
            $data['store_id'] = $order->getStoreId();
            $data[self::ORDER_ID] = $order->getId();
            $data['shipment_id'] = null;
            $data[self::ADDRESS] = $address;
            $data[self::PHONE_NUMBER] = $phoneNumber;
            $data[self::EMAIL_ADDRESS] = $emailAddress;
            $data['additional_data'] = json_encode($addtionalData);
            $data['location_id'] = $locationId;
            $orderProducingAddressModel = $this->producingAddressFactory->create();
            $orderProducingAddressModel->addData($data);
            $orderProducingAddressModel->save();
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__. ': '
                . self::ORDER_ID_TEXT . $order->getId() . self::MESSAGE_TEXT . $e->getMessage()
            );
        }
    }

    /**
     * Update Order Producing Address Data After Shipment
     *
     * @param object $order
     * @throws \Exception
     */
    public function updateOrderProducingAddressDataAfterShipment($order)
    {
        $orderId = $order->getId();
        try {
            $searchCriteria = $this->searchCriteriaBuilder->addFilter(self::ORDER_ID, $orderId)->create();
            $shipmentCollection = $this->shipmentRepositoryInterface->getList($searchCriteria);
            $shipmentData = $shipmentCollection->getItems();
            if ($shipmentData && count($shipmentData) > 0) {
                foreach ($shipmentData as $shipment) {
                    $orderProducingAddressId = $this->getOrderProducingAddressIdByOrderId($orderId);
                    $this->logger->info('Order Producing Address Id: '. $orderProducingAddressId);
                    $producingAddressModel = $this->producingAddressFactory->create()->load($orderProducingAddressId);
                    $addtionalData = $producingAddressModel->getData('additional_data');
                    $addtionalDetails = json_decode($addtionalData, true);
                    if (isset($addtionalDetails[self::ESTIMATED_TIME])) {
                        $shipment->setData('order_completion_date', $addtionalDetails[self::ESTIMATED_TIME]);
                    } else {
                        $shipment->setData('estimated_delivery_duration', $addtionalDetails['estimated_duration']);
                    }
                    $producingAddressModel->setData('shipment_id', $shipment->getId());
                    $producingAddressModel->save();
                }
            }
            $shipmentCollection->save();
            $order->save();
        } catch (Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__. ': '
                . 'Error updating shipment and order producing address data for '. self::ORDER_ID_TEXT
                . $orderId . self::MESSAGE_TEXT. $e->getMessage()
            );
        }
    }

    /**
     * Get Order Producing Address Id By Order Id
     *
     * @param int $orderId
     * @return int|null
     */
    public function getOrderProducingAddressIdByOrderId($orderId)
    {
        try {
            $producingAddress = $this->producingAddressFactory->create()->getCollection()
            ->addFieldToFilter(self::ORDER_ID, $orderId)->load();
            if (!empty($producingAddress)) {
                foreach ($producingAddress as $producingAddressData) {
                    $orderProducingAddressId = $producingAddressData->getId();
                }
            }
        } catch (Exception $e) {$this->logger->error($e->getMessage());}

        return $orderProducingAddressId ?? null;
    }

    /**
     * Get product lines details
     * @param array $rateQuoteResponse
     * @return array
     */
    public function getProductLinesDetails($rateQuoteResponse)
    {
        if (isset($rateQuoteResponse[self::OUTPUT][self::RATE_QUOTE])
            && isset($rateQuoteResponse[self::OUTPUT][self::RATE_QUOTE][self::RATE_QUOTE_DETAILS])
        ) {
            foreach ($rateQuoteResponse[self::OUTPUT][self::RATE_QUOTE][self::RATE_QUOTE_DETAILS] as $data) {
                if (isset($data['productLines'])) {
                    return $data['productLines'];
                }
            }
        }

        return [];
    }

    /**
     * Get Estimated Vs Actual Details
     * @param array $rateQuoteResponse
     * @return string|null
     */
    public function getEstimatedVsActualDetails($rateQuoteResponse)
    {
        if (isset($rateQuoteResponse[self::OUTPUT][self::RATE_QUOTE])
            && isset($rateQuoteResponse[self::OUTPUT][self::RATE_QUOTE][self::RATE_QUOTE_DETAILS])
        ) {
            foreach ($rateQuoteResponse[self::OUTPUT][self::RATE_QUOTE][self::RATE_QUOTE_DETAILS] as $data) {
                if (isset($data['estimatedVsActual'])) {

                    return $data['estimatedVsActual'];
                }
            }
        }

        return null;
    }

    /**
     * Get Responsible LocationId From rate Quote response
     *
     * @param array $rateQuoteDetails
     * @return string|null
     */
    public function getResponsibleLocationId($rateQuoteDetails)
    {
        $responsibleLocationId = null;
        foreach ($rateQuoteDetails as $key => $rateQuoteDetail) {
            if (isset($rateQuoteDetail[self::RESPONSIBLE_LOCATION_ID])
                && $rateQuoteDetail[self::RESPONSIBLE_LOCATION_ID] != '') {
                
                $responsibleLocationId = $rateQuoteDetail[self::RESPONSIBLE_LOCATION_ID];
            }
        }

        return $responsibleLocationId;
    }
}
