<?php
/**
 * @category     Fedex
 * @package      Fedex_SubmitOrderSidebar
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Test\Unit\Model;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SubmitOrderSidebar\Model\RateQuoteRequestData;
use Fedex\SubmitOrderSidebar\Model\RateQuoteRequestData\Builder as RateQuoteRequestDataBuilder;
use Fedex\SubmitOrderSidebar\Model\RateQuoteRequestData\ProductsMapper;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RateQuoteRequestDataTest extends TestCase
{
    /**
     * @var (\Fedex\EnvironmentManager\ViewModel\ToggleConfig & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $toggleConfig;
    private const FEDEX_ACCOUNT_NUMBER = 'fedex_account_number';
    private const ORDER_NUMBER = 1000000001;
    private const COMPANY_SITE = 'company_site';
    private const FIRSTNAME = 'firstname';
    private const LASTNAME = 'lastname';
    private const EMAIL = 'email';
    private const TELEPHONE = 'telephone';
    private const SHIPMENT_ID = 'shipment_id';
    private const LOCATION_ID = 'location_id';
    private const REQUESTED_PICKUP_LOCAL_TIME = 'requested_pickup_local_time';
    private const STREET_ADDRESS = ['Street Address Line 1', 'Street Address Line 2'];
    private const CITY = 'city';
    private const SHIPPER_REGION = 'shipper_region';
    private const ZIP_CODE = 'zip_code';
    private const ADDRESS_CLASSIFICATION = 'address_classification';
    private const SHIP_METHOD = 'ship_method';
    private const FEDEX_SHIP_ACCOUNT_NUMBER = 'fedex_ship_account_number';
    private const PO_NUMBER = 'po number';
    private const SOURCE_RETAIL_LOCATION_ID = 'source_retail_location_id';
    private const PROMO_CODE = ['Promo Code'];
    private const NOTES = ['note1', 'note2'];
    private const PRODUCTS = [["catalogReference" => ["value"], "instanceId" => 0,"qty" => 5]];
    private const PRODUCT_ASSOCIATIONS = [["id" => 0, "quantity" => 5]];


    /**
     * @var MockObject
     */
    private $rateQuoteRequestDataBuilder;

    /**
     * @var MockObject
     */
    private $productsMapper;

    /**
     * @var RateQuoteRequestData
     */
    private RateQuoteRequestData $rateQuoteRequestData;

    /**
     * @var Item|MockObject
     */
    private $quoteItemMock;

    protected function setUp(): void
    {
        $this->rateQuoteRequestDataBuilder = $this->getMockBuilder(RateQuoteRequestDataBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productsMapper = $this->getMockBuilder(ProductsMapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteItemMock = $this->getMockBuilder(Item::class)
            ->onlyMethods(['getOptionByCode', 'getQty'])
            ->addMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->toggleConfig = $this->createMock(ToggleConfig::class);

        $this->rateQuoteRequestData = new RateQuoteRequestData(
            $this->rateQuoteRequestDataBuilder,
            $this->productsMapper,
            $this->toggleConfig,
            []
        );

        $this->setData();
    }

    public function testGetSet() {
        $this->assertEquals(
            $this->rateQuoteRequestData->getFedexAccountNumber(),
            self::FEDEX_ACCOUNT_NUMBER
        );
        $this->assertEquals(
            $this->rateQuoteRequestData->getOrderNumber(),
            self::ORDER_NUMBER
        );
        $this->assertEquals(
            $this->rateQuoteRequestData->getCompanySite(),
            self::COMPANY_SITE
        );
        $this->assertEquals(
            $this->rateQuoteRequestData->getFirstname(),
            self::FIRSTNAME
        );
        $this->assertEquals(
            $this->rateQuoteRequestData->getLastname(),
            self::LASTNAME
        );
        $this->assertEquals(
            $this->rateQuoteRequestData->getEmail(),
            self::EMAIL
        );
        $this->assertEquals(
            $this->rateQuoteRequestData->getTelephone(),
            self::TELEPHONE
        );
        $this->assertEquals(
            $this->rateQuoteRequestData->getShipmentId(),
            self::SHIPMENT_ID
        );
        $this->assertEquals(
            $this->rateQuoteRequestData->getLocationId(),
            self::LOCATION_ID
        );
        $this->assertEquals(
            $this->rateQuoteRequestData->getRequestedPickupLocalTime(),
            self::REQUESTED_PICKUP_LOCAL_TIME
        );
        $this->assertEquals(
            $this->rateQuoteRequestData->getStreetAddress(),
            self::STREET_ADDRESS
        );
        $this->assertEquals(
            $this->rateQuoteRequestData->getCity(),
            self::CITY
        );
        $this->assertEquals(
            $this->rateQuoteRequestData->getShipperRegion(),
            self::SHIPPER_REGION
        );
        $this->assertEquals(
            $this->rateQuoteRequestData->getZipCode(),
            self::ZIP_CODE
        );
        $this->assertEquals(
            $this->rateQuoteRequestData->getAddressClassification(),
            self::ADDRESS_CLASSIFICATION
        );
        $this->assertEquals(
            $this->rateQuoteRequestData->getShipMethod(),
            self::SHIP_METHOD
        );
        $this->assertEquals(
            $this->rateQuoteRequestData->getFedexShipAccountNumber(),
            self::FEDEX_SHIP_ACCOUNT_NUMBER
        );
        $this->assertEquals(
            $this->rateQuoteRequestData->getPoNumber(),
            self::PO_NUMBER
        );
        $this->assertEquals(
            $this->rateQuoteRequestData->getSourceRetailLocationId(),
            self::SOURCE_RETAIL_LOCATION_ID
        );
        $this->assertEquals(
            $this->rateQuoteRequestData->getPromoCode(),
            self::PROMO_CODE
        );
        $this->assertEquals(
            $this->rateQuoteRequestData->getNotes(),
            self::NOTES
        );
        $this->assertEquals(
            $this->rateQuoteRequestData->getProducts(),
            self::PRODUCTS
        );
        $this->assertEquals(
            $this->rateQuoteRequestData->getProductAssociations(),
            self::PRODUCT_ASSOCIATIONS
        );
    }

    public function testGetFormmatedData() {
        $this->rateQuoteRequestDataBuilder->expects($this->once())->method('getFormmatedData');
        $this->rateQuoteRequestData->getFormmatedData(true);
    }

    public function testPopulateProducts() {
        $this->productsMapper->expects($this->once())->method('populateWithArray');
        $this->rateQuoteRequestData->populateProducts([$this->quoteItemMock]);
    }

    private function setData() {
        $this->rateQuoteRequestData->setFedexAccountNumber(self::FEDEX_ACCOUNT_NUMBER);
        $this->rateQuoteRequestData->setOrderNumber(self::ORDER_NUMBER);
        $this->rateQuoteRequestData->setCompanySite(self::COMPANY_SITE);
        $this->rateQuoteRequestData->setFirstname(self::FIRSTNAME);
        $this->rateQuoteRequestData->setLastname(self::LASTNAME);
        $this->rateQuoteRequestData->setEmail(self::EMAIL);
        $this->rateQuoteRequestData->setTelephone(self::TELEPHONE);
        $this->rateQuoteRequestData->setShipmentId(self::SHIPMENT_ID);
        $this->rateQuoteRequestData->setLocationId(self::LOCATION_ID);
        $this->rateQuoteRequestData->setRequestedPickupLocalTime(self::REQUESTED_PICKUP_LOCAL_TIME);
        $this->rateQuoteRequestData->setStreetAddress(self::STREET_ADDRESS);
        $this->rateQuoteRequestData->setCity(self::CITY);
        $this->rateQuoteRequestData->setShipperRegion(self::SHIPPER_REGION);
        $this->rateQuoteRequestData->setZipCode(self::ZIP_CODE);
        $this->rateQuoteRequestData->setAddressClassification(self::ADDRESS_CLASSIFICATION);
        $this->rateQuoteRequestData->setShipMethod(self::SHIP_METHOD);
        $this->rateQuoteRequestData->setFedexShipAccountNumber(self::FEDEX_SHIP_ACCOUNT_NUMBER);
        $this->rateQuoteRequestData->setPoNumber(self::PO_NUMBER);
        $this->rateQuoteRequestData->setPromoCode(self::PROMO_CODE);
        $this->rateQuoteRequestData->setSourceRetailLocationId(self::SOURCE_RETAIL_LOCATION_ID);
        $this->rateQuoteRequestData->setProducts(self::PRODUCTS);
        $this->rateQuoteRequestData->setProductAssociations(self::PRODUCT_ASSOCIATIONS);
        $this->rateQuoteRequestData->setNotes(self::NOTES);
    }
}
