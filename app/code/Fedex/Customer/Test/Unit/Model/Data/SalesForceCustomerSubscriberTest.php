<?php
/**
 * @category    Fedex
 * @package     Fedex_Customer
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Customer\Test\Unit\Model\Data;

use Fedex\Canva\Api\Data\UserTokenResponseInterface;
use Fedex\Customer\Api\Data\SalesForceResponseInterface;
use Fedex\Customer\Gateway\Response\SalesForce;
use Fedex\Customer\Model\Data\SalesForceCustomerSubscriber;
use PHPUnit\Framework\TestCase;

class SalesForceCustomerSubscriberTest extends TestCase
{
    /**
     * @var SalesForceCustomerSubscriber
     */
    private SalesForceCustomerSubscriber $salesForceCustomerSubscriber;
    private array $subscribedData = [
        SalesForceCustomerSubscriber::EMAIL_ADDRESS => "avo@salesforce.com.br",
        SalesForceCustomerSubscriber::COUNTRY_CODE => "US",
        SalesForceCustomerSubscriber::LANGUAGE_CODE => "EN",
        SalesForceCustomerSubscriber::FIRST_NAME => "Albert",
        SalesForceCustomerSubscriber::LAST_NAME => "Vo",
        SalesForceCustomerSubscriber::COMPANY_NAME => "Salesforce",
        SalesForceCustomerSubscriber::STREET_ADDRESS => "1234 Salesforce Rd.",
        SalesForceCustomerSubscriber::CITY_NAME => "Plano",
        SalesForceCustomerSubscriber::STATE_PROVINCE => "TX",
        SalesForceCustomerSubscriber::POSTAL_CODE => "75074"
    ];

    protected function setUp():void
    {
        $this->salesForceCustomerSubscriber = new SalesForceCustomerSubscriber($this->subscribedData);
    }

    public function testGetEmailAddress()
    {
        $this->assertEquals(
            $this->subscribedData[SalesForceCustomerSubscriber::EMAIL_ADDRESS],
            $this->salesForceCustomerSubscriber->getEmailAddress()
        );
    }

    public function testSetEmailAddress()
    {
        $newEmail = 'iagotest@fedex.com';
        $this->salesForceCustomerSubscriber->setEmailAddress($newEmail);
        $this->assertEquals($newEmail, $this->salesForceCustomerSubscriber->getEmailAddress());
    }

    public function testGetCountryCode()
    {
        $this->assertEquals(
            $this->subscribedData[SalesForceCustomerSubscriber::COUNTRY_CODE],
            $this->salesForceCustomerSubscriber->getCountryCode()
        );
    }

    public function testSetCountryCode()
    {
        $newEmail = 'iagotest@fedex.com';
        $this->salesForceCustomerSubscriber->setCountryCode($newEmail);
        $this->assertEquals($newEmail, $this->salesForceCustomerSubscriber->getCountryCode());
    }

    public function testGetLanguageCode()
    {
        $this->assertEquals(
            $this->subscribedData[SalesForceCustomerSubscriber::LANGUAGE_CODE],
            $this->salesForceCustomerSubscriber->getLanguageCode()
        );
    }

    public function testSetLanguageCode()
    {
        $newCountryCode = 'BR';
        $this->salesForceCustomerSubscriber->setLanguageCode($newCountryCode);
        $this->assertEquals($newCountryCode, $this->salesForceCustomerSubscriber->getLanguageCode());
    }

    public function testGetFirstName()
    {
        $this->assertEquals(
            $this->subscribedData[SalesForceCustomerSubscriber::FIRST_NAME],
            $this->salesForceCustomerSubscriber->getFirstName()
        );
    }

    public function testSetFirstName()
    {
        $newFirstName = 'Iago';
        $this->salesForceCustomerSubscriber->setFirstName($newFirstName);
        $this->assertEquals($newFirstName, $this->salesForceCustomerSubscriber->getFirstName());
    }

    public function testGetLastName()
    {
        $this->assertEquals(
            $this->subscribedData[SalesForceCustomerSubscriber::LAST_NAME],
            $this->salesForceCustomerSubscriber->getLastName()
        );
    }

    public function testSetLastName()
    {
        $newLastName = 'Lima';
        $this->salesForceCustomerSubscriber->setLastName($newLastName);
        $this->assertEquals($newLastName, $this->salesForceCustomerSubscriber->getLastName());
    }

    public function testGetCompanyName()
    {
        $this->assertEquals(
            $this->subscribedData[SalesForceCustomerSubscriber::COMPANY_NAME],
            $this->salesForceCustomerSubscriber->getCompanyName()
        );
    }

    public function testSetCompanyName()
    {
        $newCompanyName = 'CompanyName';
        $this->salesForceCustomerSubscriber->setCompanyName($newCompanyName);
        $this->assertEquals($newCompanyName, $this->salesForceCustomerSubscriber->getCompanyName());
    }

    public function testGetStreetAddress()
    {
        $this->assertEquals(
            $this->subscribedData[SalesForceCustomerSubscriber::STREET_ADDRESS],
            $this->salesForceCustomerSubscriber->getStreetAddress()
        );
    }

    public function testSetStreetAddress()
    {
        $newStreetAddress = '8229 Legacy Honey';
        $this->salesForceCustomerSubscriber->setStreetAddress($newStreetAddress);
        $this->assertEquals($newStreetAddress, $this->salesForceCustomerSubscriber->getStreetAddress());
    }

    public function testGetCityName()
    {
        $this->assertEquals(
            $this->subscribedData[SalesForceCustomerSubscriber::CITY_NAME],
            $this->salesForceCustomerSubscriber->getCityName()
        );
    }

    public function testSetCityName()
    {
        $newCity = 'Plano2';
        $this->salesForceCustomerSubscriber->setCityName($newCity);
        $this->assertEquals($newCity, $this->salesForceCustomerSubscriber->getCityName());
    }

    public function testGetStateProvince()
    {
        $this->assertEquals(
            $this->subscribedData[SalesForceCustomerSubscriber::STATE_PROVINCE],
            $this->salesForceCustomerSubscriber->getStateProvince()
        );
    }

    public function testSetStateProvince()
    {
        $newStateProvince = 'TX2';
        $this->salesForceCustomerSubscriber->setStateProvince($newStateProvince);
        $this->assertEquals($newStateProvince, $this->salesForceCustomerSubscriber->getStateProvince());
    }

    public function testGetPostalCode()
    {
        $this->assertEquals(
            $this->subscribedData[SalesForceCustomerSubscriber::POSTAL_CODE],
            $this->salesForceCustomerSubscriber->getPostalCode()
        );
    }

    public function testSetPostalCode()
    {
        $newPostalCode = '75024';
        $this->salesForceCustomerSubscriber->setPostalCode($newPostalCode);
        $this->assertEquals($newPostalCode, $this->salesForceCustomerSubscriber->getPostalCode());
    }
}
