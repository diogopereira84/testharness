<?php
/**
 * @category     Fedex
 * @package      Fedex_SubmitOrderSidebar
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model;

use Fedex\SubmitOrderSidebar\Api\RateQuoteRequestDataInterface;
use Fedex\SubmitOrderSidebar\Model\RateQuoteRequestData\Builder as RateQuoteRequestBuilder;
use Fedex\SubmitOrderSidebar\Model\RateQuoteRequestData\ProductsMapper;
use Magento\Framework\DataObject;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class RateQuoteRequestData extends DataObject implements RateQuoteRequestDataInterface
{
    private const FEDEX_ACCOUNT_NUMBER = 'fedex_account_number';
    private const LTE_IDENTIFIER = 'lte_identifier';
    private const ORDER_NUMBER = 'order_number';
    private const COMPANY_SITE = 'company_site';
    private const FIRSTNAME = 'firstname';
    private const LASTNAME = 'lastname';
    private const EMAIL = 'email';
    private const TELEPHONE = 'telephone';
    private const PRODUCTS = 'products';
    private const SHIPMENT_ID = 'shipment_id';
    private const LOCATION_ID = 'location_id';
    private const REQUESTED_PICKUP_LOCAL_TIME = 'requested_pickup_local_time';
    private const STREET_ADDRESS = 'street_address';
    private const CITY = 'city';
    private const SHIPPER_REGION = 'shipper_region';
    private const ZIP_CODE = 'zip_code';
    private const ADDRESS_CLASSIFICATION = 'address_classification';
    private const SHIP_METHOD = 'ship_method';
    private const FEDEX_SHIP_ACCOUNT_NUMBER = 'fedex_ship_account_number';
    private const PRODUCT_ASSOCIATIONS = 'product_associations';
    private const SOURCE_RETAIL_LOCATION_ID = 'source_retail_location_id';
    private const PROMO_CODE = 'promo_code';
    private const NOTES = 'notes';
    private const PO_NUMBER = 'po_number';

    /**
     * RateQuoteRequestData constructor.
     *
     * @param RateQuoteRequestBuilder $rateQuoteRequestBuilder
     * @param ProductsMapper $productsMapper
     * @param array $data
     */
    public function __construct(
        private RateQuoteRequestBuilder $rateQuoteRequestBuilder,
        private ProductsMapper          $productsMapper,
        private readonly ToggleConfig $toggleConfig,
        array                   $data = []
    ) {
        parent::__construct($data);
    }

    /**
     * @return string|null
     */
    public function getFedexAccountNumber(): ?string
    {
        return $this->getData(self::FEDEX_ACCOUNT_NUMBER);
    }

    /**
     * @param string|null $fedexAccountNumber
     * @return $this
     */
    public function setFedexAccountNumber(?string $fedexAccountNumber): self
    {
        return $this->setData(self::FEDEX_ACCOUNT_NUMBER, $fedexAccountNumber);
    }

    /**
     * @return string|null
     */
    public function getLteIdentifier(): ?string
    {
        return $this->getData(self::LTE_IDENTIFIER);
    }

    /**
     * @param string|null $lteIdentifier
     * @return self
     */
    public function setLteIdentifier(?string $lteIdentifier): self
    {
        return $this->setData(self::LTE_IDENTIFIER, $lteIdentifier);
    }

    /**
     * @return int
     */
    public function getOrderNumber(): int
    {
        return $this->getData(self::ORDER_NUMBER);
    }

    /**
     * @param int $orderNumber
     * @return $this
     */
    public function setOrderNumber(int $orderNumber): self
    {
        return $this->setData(self::ORDER_NUMBER, $orderNumber);
    }

    /**
     * @return string|null
     */
    public function getCompanySite(): ?string
    {
        return $this->getData(self::COMPANY_SITE);
    }

    /**
     * @param string|null $companySite
     * @return $this
     */
    public function setCompanySite(?string $companySite): self
    {
        return $this->setData(self::COMPANY_SITE, $companySite);
    }

    /**
     * @return string
     */
    public function getFirstname(): string
    {
        return $this->getData(self::FIRSTNAME);
    }

    /**
     * @param string $firstname
     * @return $this
     */
    public function setFirstname(string $firstname): self
    {
        return $this->setData(self::FIRSTNAME, $firstname);
    }

    /**
     * @return string
     */
    public function getLastname(): string
    {
        return $this->getData(self::LASTNAME);
    }

    /**
     * @param string $lastname
     * @return $this
     */
    public function setLastname(string $lastname): self
    {
        return $this->setData(self::LASTNAME, $lastname);
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->getData(self::EMAIL);
    }

    /**
     * @param string $email
     * @return $this
     */
    public function setEmail(string $email): self
    {
        return $this->setData(self::EMAIL, $email);
    }

    /**
     * @return string
     */
    public function getTelephone(): string
    {
        return $this->getData(self::TELEPHONE);
    }

    /**
     * @param string $telephone
     * @return $this
     */
    public function setTelephone(string $telephone): self
    {
        return $this->setData(self::TELEPHONE, $telephone);
    }

    /**
     * @return string
     */
    public function getShipmentId(): string
    {
        return $this->getData(self::SHIPMENT_ID);
    }

    /**
     * @param string $shipmentId
     * @return $this
     */
    public function setShipmentId(string $shipmentId): self
    {
        return $this->setData(self::SHIPMENT_ID, $shipmentId);
    }

    /**
     * @return string
     */
    public function getLocationId(): string
    {
        return $this->getData(self::LOCATION_ID);
    }

    /**
     * @param string $locationId
     * @return $this
     */
    public function setLocationId(string $locationId): self
    {
        return $this->setData(self::LOCATION_ID, $locationId);
    }

    /**
     * @return string|null
     */
    public function getRequestedPickupLocalTime(): ?string
    {
        return $this->getData(self::REQUESTED_PICKUP_LOCAL_TIME);
    }

    /**
     * @param string|null $requestedPickupLocalTime
     * @return $this
     */
    public function setRequestedPickupLocalTime(?string $requestedPickupLocalTime): self
    {
        return $this->setData(self::REQUESTED_PICKUP_LOCAL_TIME, $requestedPickupLocalTime);
    }

    /**
     * @return array|null
     */
    public function getStreetAddress(): ?array
    {
        return $this->getData(self::STREET_ADDRESS);
    }

    /**
     * @param array|null $streetAddress
     * @return $this
     */
    public function setStreetAddress(?array $streetAddress): self
    {
        return $this->setData(self::STREET_ADDRESS, $streetAddress);
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->getData(self::CITY);
    }

    /**
     * @param string $city
     * @return $this
     */
    public function setCity(string $city): self
    {
        return $this->setData(self::CITY, $city);
    }

    /**
     * @return string|null
     */
    public function getShipperRegion(): ?string
    {
        return $this->getData(self::SHIPPER_REGION);
    }

    /**
     * @param ?string $shipperRegion
     * @return $this
     */
    public function setShipperRegion(?string $shipperRegion): self
    {
        return $this->setData(self::SHIPPER_REGION, $shipperRegion);
    }

    /**
     * @return string
     */
    public function getZipCode(): string
    {
        return $this->getData(self::ZIP_CODE);
    }

    /**
     * @param string $zipcode
     * @return $this
     */
    public function setZipCode(string $zipcode): self
    {
        return $this->setData(self::ZIP_CODE, $zipcode);
    }

    /**
     * @return string
     */
    public function getAddressClassification(): string
    {
        return $this->getData(self::ADDRESS_CLASSIFICATION);
    }

    /**
     * @param string $addressClassification
     * @return $this
     */
    public function setAddressClassification(string $addressClassification): self
    {
        return $this->setData(self::ADDRESS_CLASSIFICATION, $addressClassification);
    }

    /**
     * @return string
     */
    public function getShipMethod(): string
    {
        return $this->getData(self::SHIP_METHOD);
    }

    /**
     * @param string $shipMethod
     * @return $this
     */
    public function setShipMethod(string $shipMethod): self
    {
        return $this->setData(self::SHIP_METHOD, $shipMethod);
    }

    /**
     * @return string|null
     */
    public function getFedexShipAccountNumber(): ?string
    {
        return $this->getData(self::FEDEX_SHIP_ACCOUNT_NUMBER);
    }

    /**
     * @param string|null $fedexShipAccountNumber
     * @return $this
     */
    public function setFedexShipAccountNumber(?string $fedexShipAccountNumber): self
    {
        return $this->setData(self::FEDEX_SHIP_ACCOUNT_NUMBER, $fedexShipAccountNumber);
    }

    /**
     * @return string|null
     */
    public function getPoNumber(): ?string
    {
        return $this->getData(self::PO_NUMBER);
    }

    /**
     * @param string|null $poNumber
     * @return $this
     */
    public function setPoNumber(?string $poNumber): self
    {
        return $this->setData(self::PO_NUMBER, $poNumber);
    }

    /**
     * @return array|null
     */
    public function getPromoCode(): ?array
    {
        return $this->getData(self::PROMO_CODE);
    }

    /**
     * @param array|null $promoCode
     * @return $this
     */
    public function setPromoCode(?array $promoCode): self
    {
        return $this->setData(self::PROMO_CODE, $promoCode);
    }

    /**
     * @return array|null
     */
    public function getNotes(): ?array
    {
        return $this->getData(self::NOTES);
    }

    /**
     * @param array|null $notes
     * @return $this
     */
    public function setNotes(?array $notes): self
    {
        return $this->setData(self::NOTES, $notes);
    }

    /**
     * @return string|null
     */
    public function getSourceRetailLocationId(): ?string
    {
        return $this->getData(self::SOURCE_RETAIL_LOCATION_ID);
    }

    /**
     * @param string|null $retailLocationId
     * @return $this
     */
    public function setSourceRetailLocationId(?string $retailLocationId): self
    {
        return $this->setData(self::SOURCE_RETAIL_LOCATION_ID, $retailLocationId);
    }

    /**
     * @param array $items
     */
    public function populateProducts(array $items): void
    {
        $this->productsMapper->populateWithArray($this, $items);
    }

    /**
     * @return array|null
     */
    public function getProducts(): ?array
    {
        return $this->getData(self::PRODUCTS);
    }

    /**
     * @param array $products
     * @return $this
     */
    public function setProducts(array $products): self
    {
        return $this->setData(self::PRODUCTS, $products);
    }

    /**
     * @return array|null
     */
    public function getProductAssociations(): ?array
    {
        return $this->getData(self::PRODUCT_ASSOCIATIONS);
    }

    /**
     * @param array $productAssociations
     * @return $this
     */
    public function setProductAssociations(array $productAssociations): self
    {
        return $this->setData(self::PRODUCT_ASSOCIATIONS, $productAssociations);
    }

    /**
     * @param bool $isPickup
     * @return array[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getFormmatedData(bool $isPickup): array
    {
        return $this->rateQuoteRequestBuilder->getFormmatedData($this, $isPickup);
    }
}
