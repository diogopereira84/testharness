<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Tiago Hayashi Daniel <tdaniel@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Model\Quote;

use Fedex\Cart\Api\Data\CartIntegrationInterface as  Interfarce;
use Fedex\Cart\Model\Quote\Integration;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class IntegrationTest extends TestCase
{
    /**
     * @var Integration
     */
    private Integration $integration;

    public function setUp(): void
    {
        $contextMock = $this->createMock(Context::class);
        $registryMock = $this->createMock(Registry::class);
        $abstractResourceMock = $this->getMockBuilder(AbstractResource::class)
            ->onlyMethods(['_construct', 'getConnection'])
            ->addMethods(['getIdFieldName', 'saveInSetIncluding'])
            ->getMockForAbstractClass();
        $abstractDbMock = $this->createMock(AbstractDb::class);

        $this->integration = new Integration(
            $contextMock,
            $registryMock,
            $abstractResourceMock,
            $abstractDbMock
        );
    }

    public function testGetIntegrationId()
    {
        $integrationId = 12;
        $this->integration->setData(Interfarce::KEY_INTEGRATION_ID, $integrationId);
        $this->assertEquals($integrationId, $this->integration->getIntegrationId());
    }

    public function testSetIntegrationId()
    {
        $integrationId = 12;
        $this->integration->setIntegrationId($integrationId);
        $this->assertEquals($integrationId, $this->integration->getIntegrationId());
    }

    public function testGetLocationId()
    {
        $locationId = '0798';
        $this->integration->setData(Interfarce::KEY_LOCATION_ID, $locationId);
        $this->assertEquals($locationId, $this->integration->getLocationId());
    }

    public function testSetLocationId()
    {
        $locationId = '0798';
        $this->integration->setLocationId($locationId);
        $this->assertEquals($locationId, $this->integration->getLocationId());
    }

    public function testGetStoreId()
    {
        $storeId = 'DNEK';
        $this->integration->setData(Interfarce::KEY_STORE_ID, $storeId);
        $this->assertEquals($storeId, $this->integration->getStoreId());
    }

    public function testSetStoreId()
    {
        $storeId = 'DNEK';
        $this->integration->setStoreId($storeId);
        $this->assertEquals($storeId, $this->integration->getStoreId());
    }

    public function testGetQuoteId()
    {
        $quoteId = 12;
        $this->integration->setData(Interfarce::KEY_QUOTE_ID, $quoteId);
        $this->assertEquals($quoteId, $this->integration->getQuoteId());
    }

    public function testSetQuoteId()
    {
        $quoteId = 12;
        $this->integration->setQuoteId($quoteId);
        $this->assertEquals($quoteId, $this->integration->getQuoteId());
    }

    public function testGetPickupStoreId()
    {
        $pickupStoreId = 10;
        $this->integration->setData(Interfarce::KEY_PICKUP_STORE_ID, $pickupStoreId);
        $this->assertEquals($pickupStoreId, $this->integration->getPickupStoreId());
    }

    public function testSetPickupStoreId()
    {
        $pickupStoreId = 10;
        $this->integration->setPickupStoreId($pickupStoreId);
        $this->assertEquals($pickupStoreId, $this->integration->getPickupStoreId());
    }

    public function testGetPickupLocationId()
    {
        $pickupLocationId = 10;
        $this->integration->setData(Interfarce::KEY_PICKUP_LOCATION_ID, $pickupLocationId);
        $this->assertEquals($pickupLocationId, $this->integration->getPickupLocationId());
    }

    public function testSetPickupLocationId()
    {
        $pickupLocationId = 10;
        $this->integration->setPickupLocationId($pickupLocationId);
        $this->assertEquals($pickupLocationId, $this->integration->getPickupLocationId());
    }

    public function testGetSetPickupLocationDate(): void
    {
        $pickupLocationDate = "2023-12-31 00:00:00";
        $this->integration->setPickupLocationDate($pickupLocationDate);
        $this->assertEquals($pickupLocationDate, $this->integration->getPickupLocationDate());
    }

    public function testGetSetRetailCustomerId(): void
    {
        $retailCustomerId = "123456";
        $this->integration->setRetailCustomerId($retailCustomerId);
        $this->assertEquals($retailCustomerId, $this->integration->getRetailCustomerId());
    }

    public function testGetSetRaqNetAmount(): void
    {
        $raqNetAmount = 19.50;
        $this->integration->setRaqNetAmount($raqNetAmount);
        $this->assertEquals($raqNetAmount, $this->integration->getRaqNetAmount());
    }

    public function testGetSetDeliveryData(): void
    {
        $this->integration->setDeliveryData('{}');
        $this->assertEquals('{}', $this->integration->getDeliveryData());
    }

    public function testGetSetRetryTransactionApi(): void
    {
        $this->integration->setRetryTransactionApi(true);
        $this->assertEquals(true, $this->integration->getRetryTransactionApi());
    }

    public function testGetSetFjmpRateQuoteId(): void
    {
        $this->integration->setFjmpRateQuoteId('some-rate-quote-id');
        $this->assertEquals('some-rate-quote-id', $this->integration->getFjmpRateQuoteId());
    }
}
