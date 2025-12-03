<?php
/**
 * @category     Fedex
 * @package      Fedex_Customer
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Customer\Api\Data;

interface SalesForceCustomerSubscriberInterface
{
    public const EMAIL_ADDRESS = "email_address";
    public const COUNTRY_CODE = "country_code";
    public const LANGUAGE_CODE = "language_code";
    public const FIRST_NAME = "first_name";
    public const LAST_NAME = "last_name";
    public const COMPANY_NAME = "company_name";
    public const STREET_ADDRESS = "street_address";
    public const CITY_NAME = "city_name";
    public const STATE_PROVINCE = "state_province";
    public const POSTAL_CODE = "postal_code";

    /**
     * Getter for EmailAddress.
     *
     * @return string|null
     */
    public function getEmailAddress(): ?string;

    /**
     * Setter for ShipmentStatus.
     *
     * @param string|null $emailAddress
     *
     * @return void
     */
    public function setEmailAddress(?string $emailAddress): void;

    /**
     * Getter for CountryCode.
     *
     * @return string|null
     */
    public function getCountryCode(): ?string;

    /**
     * Setter for CountryCode.
     *
     * @param string|null $countryCode
     *
     * @return void
     */
    public function setCountryCode(?string $countryCode): void;

    /**
     * Getter for CountryCode.
     *
     * @return string|null
     */
    public function getLanguageCode(): ?string;

    /**
     * Setter for LanguageCode.
     *
     * @param string|null $languageCode
     *
     * @return void
     */
    public function setLanguageCode(?string $languageCode): void;

    /**
     * Getter for FirstName.
     *
     * @return string|null
     */
    public function getFirstName(): ?string;

    /**
     * Setter for FirstName.
     *
     * @param string|null $firstName
     *
     * @return void
     */
    public function setFirstName(?string $firstName): void;

    /**
     * Getter for LastName.
     *
     * @return string|null
     */
    public function getLastName(): ?string;

    /**
     * Setter for LastName.
     *
     * @param string|null $lastName
     *
     * @return void
     */
    public function setLastName(?string $lastName): void;

    /**
     * Getter for CompanyName.
     *
     * @return string|null
     */
    public function getCompanyName(): ?string;

    /**
     * Setter for CompanyName.
     *
     * @param string|null $companyName
     *
     * @return void
     */
    public function setCompanyName(?string $companyName): void;

    /**
     * Getter for StreetAddress.
     *
     * @return string|null
     */
    public function getStreetAddress(): ?string;

    /**
     * Setter for StreetAddress.
     *
     * @param string|null $streetAddress
     *
     * @return void
     */
    public function setStreetAddress(?string $streetAddress): void;

    /**
     * Getter for CityName.
     *
     * @return string|null
     */
    public function getCityName(): ?string;

    /**
     * Setter for CityName.
     *
     * @param string|null $cityName
     *
     * @return void
     */
    public function setCityName(?string $cityName): void;

    /**
     * Getter for StateProvince.
     *
     * @return string|null
     */
    public function getStateProvince(): ?string;

    /**
     * Setter for StateProvince.
     *
     * @param string|null $stateProvince
     *
     * @return void
     */
    public function setStateProvince(?string $stateProvince): void;

    /**
     * Getter for PostalCode.
     *
     * @return string|null
     */
    public function getPostalCode(): ?string;

    /**
     * Setter for PostalCode.
     *
     * @param string|null $postalCode
     *
     * @return void
     */
    public function setPostalCode(?string $postalCode): void;
}
