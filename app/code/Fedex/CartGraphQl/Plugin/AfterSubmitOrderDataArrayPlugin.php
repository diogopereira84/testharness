<?php
/**
 * @category    Fedex
 * @package     Fedex_CartGraphQl
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Athira Indrakumar <athiraindrakumar.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Plugin;

use Fedex\GraphQl\Model\RequestQueryValidator;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderDataArray;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Fedex\CartGraphQl\Model\RateQuote\ShippingDelivery;
use Fedex\InStoreConfigurations\Model\Organization;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;

/**
 * Plugin class to enrich order submission data with additional contact and delivery information.
 *
 * This plugin modifies the output of specific methods in the SubmitOrderDataArray class by:
 * - Appending contact and shipping details for delivery orders.
 * - Injecting customer contact info into rate quote request payloads.
 *
 * The plugin is only active when GraphQL requests are detected and specific configuration flags are enabled.
 *
 * @category Fedex
 * @package  Fedex_CartGraphQl
 */
class AfterSubmitOrderDataArrayPlugin
{
    private const CONTACT = 'contact';
    private const CONTACT_ID = 'contactId';
    private const PERSON_NAME = 'personName';
    private const FIRST_NAME = 'firstName';
    private const LAST_NAME = 'lastName';
    private const COMPANY = 'company';
    private const EMAIL_DETAIL = 'emailDetail';
    private const EMAIL_ADDRESS = 'emailAddress';
    private const PHONE_NUMBER_DETAILS = 'phoneNumberDetails';
    private const PHONE_NUMBER = 'phoneNumber';
    private const NUMBER = 'number';
    private const EXTENSION = 'extension';
    private const USAGE = 'usage';
    private const PRIMARY = 'PRIMARY';
    private const REQUESTED_DELIVERY_LOCAL_TIME = 'requestedDeliveryLocalTime';

    /**
     * @param InstoreConfig $instoreConfig
     * @param ShippingDelivery $shippingDelivery
     * @param RequestQueryValidator $requestQueryValidator
     * @param Organization $organization
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     */
    public function __construct(
        private readonly InstoreConfig $instoreConfig,
        private readonly ShippingDelivery $shippingDelivery,
        private readonly RequestQueryValidator $requestQueryValidator,
        private readonly Organization $organization,
        private readonly CartIntegrationRepositoryInterface $cartIntegrationRepository
    ) {}

    /**
     * Plugin for SubmitOrderDataArray::getReceipientInfo
     *
     * @param SubmitOrderDataArray $subject
     * @param mixed $result
     * @param bool $isPickup
     * @param mixed $recipientDataObject
     * @param bool $isOrderApproval
     * @return array
     */
    public function afterGetReceipientInfo(
        SubmitOrderDataArray $subject,
                             $result,
                             $isPickup,
                             $recipientDataObject,
                             $isOrderApproval = false
    ): array {
        return $this->processRecipientData($result, $isPickup, $recipientDataObject);
    }

    /**
     * Plugin for SubmitOrderDataArray::getReceipientInfoUpdated
     *
     * @param SubmitOrderDataArray $subject
     * @param mixed $result
     * @param bool $isPickup
     * @param mixed $recipientDataObject
     * @param bool $isOrderApproval
     * @return array
     */
    public function afterGetReceipientInfoUpdated(
        SubmitOrderDataArray $subject,
                             $result,
                             $isPickup,
                             $recipientDataObject,
                             $isOrderApproval = false
    ): array {
        return $this->processRecipientData($result, $isPickup, $recipientDataObject);
    }

    /**
     * Plugin for SubmitOrderDataArray::prepareShippingRateQuoteRequestData
     *
     * Adds customer contact details to the rate quote request structure.
     *
     * @param SubmitOrderDataArray $subject
     * @param mixed $result
     * @param mixed $rateQuoteId
     * @param mixed $action
     * @param mixed $dataObject
     * @param mixed $quote
     * @param bool $isOrderApproval
     * @return array
     */
    public function afterPrepareShippingRateQuoteRequestData(
        SubmitOrderDataArray $subject,
                             $result,
                             $rateQuoteId,
                             $action,
                             $dataObject,
                             $quote,
                             $isOrderApproval = false
    ): array {
        return $this->setOrderContact($quote, $result);
    }

    /**
     * Enriches recipient data with local or external delivery details and contact information.
     *
     * @param mixed $result
     * @param bool $isPickup
     * @param mixed $recipientDataObject
     * @return array
     */
    private function processRecipientData($result, bool $isPickup, $recipientDataObject): array
    {
        if (!$this->instoreConfig->isEnableServiceTypeForRAQ() || !$this->requestQueryValidator->isGraphQl()) {
            return $result;
        }

        if (!is_array($result[0])) {
            return $result;
        }

        $shippingAddress = $recipientDataObject->getQuote()->getShippingAddress();

        if ($this->instoreConfig->isDeliveryDatesFieldsEnabled()) {
            $result[0][self::REQUESTED_DELIVERY_LOCAL_TIME] =
                $this->getEstimatedDueDate((int)$recipientDataObject->getQuote()->getId());
        }

        if ($isPickup) {
            return $result;
        }

        $result[0][self::CONTACT] = $this->buildContactData($recipientDataObject, $shippingAddress);

        if (!isset($result[0]['shipmentDelivery'])) {
            return $result;
        }

        $shipperRegion = $recipientDataObject->getShipperRegion();
        $shipperRegionCode = is_object($shipperRegion) ? (string)$shipperRegion->getData('code') : (string)$shipperRegion;

        $deliveryType = $this->shippingDelivery->validateIfLocalDelivery($recipientDataObject->getShipMethod())
            ? ShippingDelivery::LOCAL_DELIVERY
            : ShippingDelivery::EXTERNAL_DELIVERY;

        $deliveryData = $deliveryType === ShippingDelivery::LOCAL_DELIVERY
            ? $this->shippingDelivery->setLocalDelivery($recipientDataObject, $shipperRegionCode)
            : $this->shippingDelivery->setExternalDelivery($recipientDataObject, $shipperRegionCode);

        unset($result[0]['shipmentDelivery']);
        $result[0][$deliveryType] = $deliveryData;

        return $result;
    }

    /**
     * Builds the contact information array from shipping address and recipient data.
     *
     * @param mixed $recipientDataObject
     * @param mixed $shippingAddress
     * @return array
     */
    private function buildContactData($recipientDataObject, $shippingAddress): array
    {
        return [
            self::CONTACT_ID => $recipientDataObject->getContactId(),
            self::PERSON_NAME => [
                self::FIRST_NAME => $shippingAddress->getFirstname(),
                self::LAST_NAME => $shippingAddress->getLastname(),
            ],
            self::COMPANY => [
                'name' => $this->organization->getOrganization($shippingAddress->getCompany() ?? '')
            ],
            self::EMAIL_DETAIL => [
                self::EMAIL_ADDRESS => $shippingAddress->getEmail(),
            ],
            self::PHONE_NUMBER_DETAILS => [[
                self::PHONE_NUMBER => [
                    self::NUMBER => $shippingAddress->getTelephone(),
                    self::EXTENSION => $shippingAddress->getExtNo(),
                ],
                self::USAGE => self::PRIMARY,
            ]],
        ];
    }

    /**
     * Sets order contact information for rate quote request data.
     *
     * @param mixed $quote
     * @param array $result
     * @return array
     */
    private function setOrderContact($quote, $result): array
    {
        if (!$this->instoreConfig->isEnableServiceTypeForRAQ() || !$this->requestQueryValidator->isGraphQl()) {
            return $result;
        }

        $quoteBill = $quote->getBillingAddress();

        $result['rateQuoteRequest']['retailPrintOrder']['orderContact'][self::CONTACT] = [
            self::PERSON_NAME => [
                self::FIRST_NAME => $quote->getCustomerFirstname(),
                self::LAST_NAME => $quote->getCustomerLastname(),
            ],
            self::COMPANY => [
                'name' => $this->organization->getOrganization($quoteBill->getCompany() ?? '')
            ],
            self::EMAIL_DETAIL => [
                self::EMAIL_ADDRESS => $quote->getCustomerEmail(),
            ],
            self::PHONE_NUMBER_DETAILS => [[
                self::PHONE_NUMBER => [
                    self::NUMBER => trim((string)$quote->getCustomerTelephone()),
                    self::EXTENSION => $quote->getExtNo(),
                ],
                self::USAGE => self::PRIMARY,
            ]],
        ];

        return $result;
    }

    /**
     * Retrieves the estimated pickup/delivery date in ISO-8601 format.
     *
     * @param int|null $quoteId
     * @return string|null
     */
    private function getEstimatedDueDate(?int $quoteId): ?string
    {
        if (!$quoteId) {
            return null;
        }

        $integration = $this->cartIntegrationRepository->getByQuoteId($quoteId);
        $pickupDate = $integration->getPickupLocationDate();

        return $pickupDate ? str_replace(' ', 'T', $pickupDate) : null;
    }
}
