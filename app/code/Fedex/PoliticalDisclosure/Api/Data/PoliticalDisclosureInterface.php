<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\PoliticalDisclosure\Api\Data;

interface PoliticalDisclosureInterface
{
    /**
     * Order ID (sales_order.entity_id).
     *
     * @return int|null
     */
    public function getOrderId(): ?int;

    /**
     * @param int|null $value Order ID.
     * @return $this
     */
    public function setOrderId(?int $value);

    /**
     * Quote ID (quote.entity_id).
     *
     * @return int|null
     */
    public function getQuoteId(): ?int;

    /**
     * @param int|null $value Quote ID.
     * @return $this
     */
    public function setQuoteId(?int $value);

    /**
     * Disclosure status (1 = Active, 0 = Inactive).
     *
     * @return int|null
     */
    public function getDisclosureStatus(): ?int;

    /**
     * @param int|null $value 1 for Active, 0 for Inactive.
     * @return $this
     */
    public function setDisclosureStatus(?int $value);

    /**
     * Candidate / PAC / Ballot issue text (description).
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * @param string|null $value Candidate, PAC, or ballot issue.
     * @return $this
     */
    public function setDescription(?string $value);

    /**
     * Election date in ISO format (YYYY-MM-DD).
     *
     * @return string|null
     */
    public function getElectionDate(): ?string;

    /**
     * @param string|null $value Date as YYYY-MM-DD.
     * @return $this
     */
    public function setElectionDate(?string $value);

    /**
     * Election region/state ID (directory_country_region.region_id).
     *
     * @return int|null
     */
    public function getElectionStateId(): ?int;

    /**
     * @param int|null $value Region/State ID for the election.
     * @return $this
     */
    public function setElectionStateId(?int $value);

    /**
     * Sponsoring committee or entity (if applicable).
     *
     * @return string|null
     */
    public function getSponsor(): ?string;

    /**
     * @param string|null $value Sponsoring committee/entity.
     * @return $this
     */
    public function setSponsor(?string $value);

    /**
     * Address street lines (may contain multiple lines concatenated).
     *
     * @return string|null
     */
    public function getAddressStreetLines(): ?string;

    /**
     * @param string|null $value Full street lines.
     * @return $this
     */
    public function setAddressStreetLines(?string $value);

    /**
     * City.
     *
     * @return string|null
     */
    public function getCity(): ?string;

    /**
     * @param string|null $value City.
     * @return $this
     */
    public function setCity(?string $value);

    /**
     * Address region/state ID (directory_country_region.region_id).
     *
     * @return int|null
     */
    public function getRegionId(): ?int;

    /**
     * @param int|null $value Region/State ID for the address.
     * @return $this
     */
    public function setRegionId(?int $value);

    /**
     * ZIP/Postal code.
     *
     * @return string|null
     */
    public function getZipCode(): ?string;

    /**
     * @param string|null $value ZIP code.
     * @return $this
     */
    public function setZipCode(?string $value);

    /**
     * Contact email (optional).
     *
     * @return string|null
     */
    public function getEmail(): ?string;

    /**
     * @param string|null $value Email address.
     * @return $this
     */
    public function setEmail(?string $value);
}
