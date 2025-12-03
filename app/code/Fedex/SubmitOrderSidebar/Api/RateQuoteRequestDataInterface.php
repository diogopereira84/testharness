<?php
/**
 * @category     Fedex
 * @package      Fedex_SubmitOrderSidebar
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Api;

interface RateQuoteRequestDataInterface
{
    /**
     * @return string|null
     */
    public function getFedexAccountNumber(): ?string;

    /**
     * @param string|null $lteIdentifier
     * @return self
     */
    public function setLteIdentifier(?string $lteIdentifier): self;

    /**
     * @return string|null
     */
    public function getLteIdentifier(): ?string;

    /**
     * @param string|null $fedexAccountNumber
     * @return self
     */
    public function setFedexAccountNumber(?string $fedexAccountNumber): self;

    /**
     * @return int
     */
    public function getOrderNumber(): int;

    /**
     * @param int $orderNumber
     * @return self
     */
    public function setOrderNumber(int $orderNumber): self;

    /**
     * @return string|null
     */
    public function getCompanySite(): ?string;

    /**
     * @param string|null $companySite
     * @return self
     */
    public function setCompanySite(?string $companySite): self;

    /**
     * @return string
     */
    public function getFirstname(): string;

    /**
     * @param string $firstname
     * @return self
     */
    public function setFirstname(string $firstname): self;

    /**
     * @return string
     */
    public function getLastname(): string;

    /**
     * @param string $lastname
     * @return self
     */
    public function setLastname(string $lastname): self;

    /**
     * @return string
     */
    public function getEmail(): string;

    /**
     * @param string $email
     * @return self
     */
    public function setEmail(string $email): self;

    /**
     * @return string
     */
    public function getTelephone(): string;

    /**
     * @param string $telephone
     * @return self
     */
    public function setTelephone(string $telephone): self;

    /**
     * @return string
     */
    public function getShipmentId(): string;

    /**
     * @param string $shipmentId
     * @return self
     */
    public function setShipmentId(string $shipmentId): self;

    /**
     * @return string
     */
    public function getLocationId(): string;

    /**
     * @param string $locationId
     * @return self
     */
    public function setLocationId(string $locationId): self;

    /**
     * @return string|null
     */
    public function getRequestedPickupLocalTime(): ?string;

    /**
     * @param string|null $requestedPickupLocalTime
     * @return self
     */
    public function setRequestedPickupLocalTime(?string $requestedPickupLocalTime): self;

    /**
     * @return array|null
     */
    public function getStreetAddress(): ?array;

    /**
     * @param array|null $streetAddress
     * @return self
     */
    public function setStreetAddress(?array $streetAddress): self;

    /**
     * @return string
     */
    public function getCity(): string;

    /**
     * @param string $city
     * @return self
     */
    public function setCity(string $city): self;

    /**
     * @return string|null
     */
    public function getShipperRegion(): ?string;

    /**
     * @param string|null $shipperRegion
     * @return self
     */
    public function setShipperRegion(?string $shipperRegion): self;

    /**
     * @return string
     */
    public function getZipCode(): string;

    /**
     * @param string $zipcode
     * @return self
     */
    public function setZipCode(string $zipcode): self;

    /**
     * @return string
     */
    public function getAddressClassification(): string;

    /**
     * @param string $addressClassification
     * @return self
     */
    public function setAddressClassification(string $addressClassification): self;

    /**
     * @return string
     */
    public function getShipMethod(): string;

    /**
     * @param string $shipMethod
     * @return self
     */
    public function setShipMethod(string $shipMethod): self;

    /**
     * @return string|null
     */
    public function getFedexShipAccountNumber(): ?string;

    /**
     * @param string|null $fedexShipAccountNumber
     * @return self
     */
    public function setFedexShipAccountNumber(?string $fedexShipAccountNumber): self;

    /**
     * @return string|null
     */
    public function getPoNumber(): ?string;

    /**
     * @param string|null $poNumber
     * @return self
     */
    public function setPoNumber(?string $poNumber): self;

    /**
     * @return array|null
     */
    public function getPromoCode(): ?array;

    /**
     * @param array|null $promoCode
     * @return self
     */
    public function setPromoCode(?array $promoCode): self;

    /**
     * @return string|null
     */
    public function getSourceRetailLocationId(): ?string;

    /**
     * @param string|null $retailLocationId
     * @return self
     */
    public function setSourceRetailLocationId(?string $retailLocationId): self;

    /**
     * @param array $items
     * @return void
     */
    public function populateProducts(array $items): void;

    /**
     * @return array|null
     */
    public function getProducts(): ?array;

    /**
     * @param array $products
     * @return self
     */
    public function setProducts(array $products): self;

    /**
     * @return array|null
     */
    public function getProductAssociations(): ?array;

    /**
     * @param array $productAssociations
     * @return self
     */
    public function setProductAssociations(array $productAssociations): self;

    /**
     * @param bool $isPickup
     * @return array
     */
    public function getFormmatedData(bool $isPickup): array;
}
