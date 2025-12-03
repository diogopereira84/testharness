<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\PoliticalDisclosure\Model\Data;

use Fedex\PoliticalDisclosure\Api\Data\PoliticalDisclosureInterface;

class PoliticalDisclosure implements PoliticalDisclosureInterface
{
    private ?int $orderId = null;
    private ?int $quoteId = null;
    private int $disclosureStatus = 1;
    private ?string $description = null;
    private ?string $electionDate = null;
    private ?int $electionStateId = null;
    private ?string $sponsor = null;
    private ?string $addressStreetLines = null;
    private ?string $city = null;
    private ?int $regionId = null;
    private ?string $zipCode = null;
    private ?string $email = null;

    /**
     * {@inheritdoc}
     */
    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    /**
     * {@inheritdoc}
     */
    public function setOrderId(?int $value)
    {
        $this->orderId = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuoteId(): ?int
    {
        return $this->quoteId;
    }

    /**
     * {@inheritdoc}
     */
    public function setQuoteId(?int $value)
    {
        $this->quoteId = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisclosureStatus(): ?int
    {
        return $this->disclosureStatus ? 1 : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function setDisclosureStatus(?int $value)
    {
        $this->disclosureStatus = (int)($value ?? 1) ? 1 : 0;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * {@inheritdoc}
     */
    public function setDescription(?string $value)
    {
        $this->description = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getElectionDate(): ?string
    {
        return $this->electionDate;
    }

    /**
     * {@inheritdoc}
     */
    public function setElectionDate(?string $value)
    {
        $this->electionDate = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getElectionStateId(): ?int
    {
        return $this->electionStateId;
    }

    /**
     * {@inheritdoc}
     */
    public function setElectionStateId(?int $value)
    {
        $this->electionStateId = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSponsor(): ?string
    {
        return $this->sponsor;
    }

    /**
     * {@inheritdoc}
     */
    public function setSponsor(?string $value)
    {
        $this->sponsor = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAddressStreetLines(): ?string
    {
        return $this->addressStreetLines;
    }

    /**
     * {@inheritdoc}
     */
    public function setAddressStreetLines(?string $value)
    {
        $this->addressStreetLines = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * {@inheritdoc}
     */
    public function setCity(?string $value)
    {
        $this->city = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRegionId(): ?int
    {
        return $this->regionId;
    }

    /**
     * {@inheritdoc}
     */
    public function setRegionId(?int $value)
    {
        $this->regionId = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    /**
     * {@inheritdoc}
     */
    public function setZipCode(?string $value)
    {
        $this->zipCode = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * {@inheritdoc}
     */
    public function setEmail(?string $value)
    {
        $this->email = $value;
        return $this;
    }
}
