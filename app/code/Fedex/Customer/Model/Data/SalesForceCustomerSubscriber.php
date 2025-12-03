<?php
/**
 * @category     Fedex
 * @package      Fedex_Customer
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Customer\Model\Data;

use Fedex\Customer\Api\Data\SalesForceCustomerSubscriberInterface;
use Magento\Framework\DataObject;

class SalesForceCustomerSubscriber extends DataObject implements SalesForceCustomerSubscriberInterface
{
    /**
     * @inheritDoc
     */
    public function getEmailAddress(): ?string
    {
        return $this->getData(static::EMAIL_ADDRESS);
    }

    /**
     * @inheritDoc
     */
    public function setEmailAddress(?string $emailAddress): void
    {
        $this->setData(static::EMAIL_ADDRESS, $emailAddress);
    }
        /**
         * @inheritDoc
         */
    public function getCountryCode(): ?string
    {
        return $this->getData(static::COUNTRY_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setCountryCode(?string $countryCode): void
    {
        $this->setData(static::COUNTRY_CODE, $countryCode);
    }

    /**
     * @inheritDoc
     */
    public function getLanguageCode(): ?string
    {
        return $this->getData(static::LANGUAGE_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setLanguageCode(?string $languageCode): void
    {
        $this->setData(static::LANGUAGE_CODE, $languageCode);
    }

    /**
     * @inheritDoc
     */
    public function getFirstName(): ?string
    {
        return $this->getData(static::FIRST_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setFirstName(?string $firstName): void
    {
        $this->setData(static::FIRST_NAME, $firstName);
    }

    /**
     * @inheritDoc
     */
    public function getLastName(): ?string
    {
        return $this->getData(static::LAST_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setLastName(?string $lastName): void
    {
        $this->setData(static::LAST_NAME, $lastName);
    }

    /**
     * @inheritDoc
     */
    public function getCompanyName(): ?string
    {
        return $this->getData(static::COMPANY_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setCompanyName(?string $companyName): void
    {
        $this->setData(static::COMPANY_NAME, $companyName);
    }

    /**
     * @inheritDoc
     */
    public function getStreetAddress(): ?string
    {
        return $this->getData(static::STREET_ADDRESS);
    }

    /**
     * @inheritDoc
     */
    public function setStreetAddress(?string $streetAddress): void
    {
        $this->setData(static::STREET_ADDRESS, $streetAddress);
    }

    /**
     * @inheritDoc
     */
    public function getCityName(): ?string
    {
        return $this->getData(static::CITY_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setCityName(?string $cityName): void
    {
        $this->setData(static::CITY_NAME, $cityName);
    }

    /**
     * @inheritDoc
     */
    public function getStateProvince(): ?string
    {
        return $this->getData(static::STATE_PROVINCE);
    }

    /**
     * @inheritDoc
     */
    public function setStateProvince(?string $stateProvince): void
    {
        $this->setData(static::STATE_PROVINCE, $stateProvince);
    }

    /**
     * @inheritDoc
     */
    public function getPostalCode(): ?string
    {
        return $this->getData(static::POSTAL_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setPostalCode(?string $postalCode): void
    {
        $this->setData(static::POSTAL_CODE, $postalCode);
    }
}
