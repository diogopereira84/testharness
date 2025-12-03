<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Test\Unit\Model\RateQuoteRequestData;

use Fedex\SubmitOrderSidebar\Helper\Data as SubmitOrderHelper;
use Fedex\SubmitOrderSidebar\Api\RateQuoteRequestDataInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\SubmitOrderSidebar\Model\RateQuoteRequestData\Builder;
use Fedex\ComputerRental\Model\CRdataModel;
class BuilderTest extends TestCase
{
    /**
     * @var (\Fedex\ComputerRental\Model\CRdataModel & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $crDataModelMock;
    // @codingStandardsIgnoreStart
    private const EXPECTED_RETURN_PICKUP = '{"rateQuoteRequest":{"sourceRetailLocationId":"source_retail_location_id","previousQuoteId":null,"action":"SAVE_COMMIT","retailPrintOrder":{"fedExAccountNumber":"fedex_account_number","origin":{"orderNumber":1000000001,"orderClient":"MAGENTO","site":"company_site","userReferences":null,"fedExLocationId":null},"orderContact":{"contact":{"contactId":null,"personName":{"firstName":"firstname","lastName":"lastname"},"company":{"name":"FXO"},"emailDetail":{"emailAddress":"email"},"phoneNumberDetails":[{"phoneNumber":{"number":"telephone","extension":null},"usage":"PRIMARY"}]}},"customerNotificationEnabled":false,"notificationRegistration":{"webhook":{"url":"https:\/\/staging3.office.fedex.comrest\/V1\/fedexoffice\/orders\/1000000001\/status","auth":null}},"profileAccountId":null,"expirationDays":"30","products":[{"catalogReference":["value"],"instanceId":0,"qty":5}],"recipients":[{"reference":"shipment_id","contact":{"contactId":null,"personName":{"firstName":"firstname","lastName":"lastname"},"company":{"name":"FXO"},"emailDetail":{"emailAddress":"email"},"phoneNumberDetails":[{"phoneNumber":{"number":"telephone","extension":null},"usage":"PRIMARY"}]},"productAssociations":[{"id":0,"quantity":5}],"pickUpDelivery":{"location":{"id":"location_id"},"requestedPickupLocalTime":"requested_pickup_local_time"}}]},"coupons":null,"teamMemberId":null}}';
    private const EXPECTED_RETURN_DELIVERY = '{"rateQuoteRequest":{"sourceRetailLocationId":"source_retail_location_id","previousQuoteId":null,"action":"SAVE_COMMIT","retailPrintOrder":{"fedExAccountNumber":"fedex_account_number","origin":{"orderNumber":1000000001,"orderClient":"MAGENTO","site":"company_site","userReferences":null,"fedExLocationId":null},"orderContact":{"contact":{"contactId":null,"personName":{"firstName":"firstname","lastName":"lastname"},"company":{"name":"FXO"},"emailDetail":{"emailAddress":"email"},"phoneNumberDetails":[{"phoneNumber":{"number":"telephone","extension":null},"usage":"PRIMARY"}]}},"customerNotificationEnabled":false,"notificationRegistration":{"webhook":{"url":"https:\/\/staging3.office.fedex.comrest\/V1\/fedexoffice\/orders\/1000000001\/status","auth":null}},"profileAccountId":null,"expirationDays":"30","products":[{"catalogReference":["value"],"instanceId":0,"qty":5}],"recipients":[{"reference":"shipment_id","contact":{"contactId":null,"personName":{"firstName":"firstname","lastName":"lastname"},"company":{"name":"FXO"},"emailDetail":{"emailAddress":"email"},"phoneNumberDetails":[{"phoneNumber":{"number":"telephone","extension":null},"usage":"PRIMARY"}]},"productAssociations":[{"id":0,"quantity":5}],"shipmentDelivery":{"address":{"streetLines":["Street Address Line 1","Street Address Line 2"],"city":"city","stateOrProvinceCode":"shipper_region","postalCode":"zip_code","countryCode":"US","addressClassification":"address_classification"},"holdUntilDate":null,"serviceType":"ship_method","fedExAccountNumber":"fedex_ship_account_number","deliveryInstructions":null,"poNumber":"po_number"}}]},"coupons":null,"teamMemberId":null}}';
    // @codingStandardsIgnoreEnd

    /**
     * @var MockObject
     */
    private MockObject $storeManagerMock;

    /**
     * @var MockObject
     */
    private MockObject $submitOrderHelperMock;

    /**
     * @var MockObject
     */
    private MockObject $rateQuoteRequest;

    /**
     * @var Builder
     */
    private Builder $rateQuoteRequestData;

    protected function setUp(): void
    {
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->onlyMethods(['getStore'])
            ->addMethods(['getBaseUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->submitOrderHelperMock = $this->getMockBuilder(SubmitOrderHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->crDataModelMock = $this->getMockBuilder(CRdataModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->rateQuoteRequestData = new Builder(
            $this->storeManagerMock,
            $this->submitOrderHelperMock,
            $this->crDataModelMock
        );

        $this->setData();
    }

    public function testGetFormmatedDataForDelivery() {
        $this->rateQuoteRequest->expects($this->once())->method('getShipperRegion')
            ->willReturn('shipper_region');
        $this->rateQuoteRequest->expects($this->once())->method('getStreetAddress')
            ->willReturn(["Street Address Line 1","Street Address Line 2"]);
        $this->rateQuoteRequest->expects($this->once())->method('getCity')
            ->willReturn('city');
        $this->rateQuoteRequest->expects($this->once())->method('getZipCode')
            ->willReturn('zip_code');
        $this->rateQuoteRequest->expects($this->once())->method('getAddressClassification')
            ->willReturn('address_classification');
        $this->rateQuoteRequest->expects($this->once())->method('getShipMethod')
            ->willReturn('ship_method');
        $this->rateQuoteRequest->expects($this->once())->method('getFedexShipAccountNumber')
            ->willReturn('fedex_ship_account_number');
        $this->rateQuoteRequest->expects($this->once())->method('getPoNumber')
            ->willReturn('po_number');

        $data = $this->rateQuoteRequestData->getFormmatedData($this->rateQuoteRequest, false);
        $expectedData = json_decode(self::EXPECTED_RETURN_DELIVERY, true);

        $this->assertEquals($data, $expectedData);
    }

    public function testGetFormmatedDataForPickup() {
        $this->rateQuoteRequest->expects($this->once())->method('getLocationId')->willReturn('location_id');
        $this->rateQuoteRequest->expects($this->once())->method('getRequestedPickupLocalTime')->willReturn('requested_pickup_local_time');

        $data = $this->rateQuoteRequestData->getFormmatedData($this->rateQuoteRequest, true);
        $expectedData = json_decode(self::EXPECTED_RETURN_PICKUP, true);

        $this->assertEquals($data, $expectedData);
    }

    private function setData() {
        $this->storeManagerMock->expects($this->once())->method('getStore')->willReturnSelf();
        $this->storeManagerMock->expects($this->once())
            ->method('getBaseUrl')->willReturn('https://staging3.office.fedex.com');

        $this->rateQuoteRequest = $this->getMockBuilder(RateQuoteRequestDataInterface::class)
            ->onlyMethods([
                'getOrderNumber',
                'getCompanySite',
                'getSourceRetailLocationId',
                'getFedexAccountNumber',
                'getFirstname',
                'getLastname',
                'getEmail',
                'getTelephone',
                'getProductAssociations',
                'getProducts',
                'getShipmentId',
                'getStreetAddress',
                'getCity',
                'getShipperRegion',
                'getZipCode',
                'getAddressClassification',
                'getShipMethod',
                'getFedexShipAccountNumber',
                'getPoNumber',
                'getLocationId',
                'getRequestedPickupLocalTime'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->rateQuoteRequest->expects($this->exactly(2))->method('getOrderNumber')
            ->willReturn(1000000001);
        $this->rateQuoteRequest->expects($this->once())->method('getCompanySite')
            ->willReturn('company_site');
        $this->rateQuoteRequest->expects($this->once())->method('getSourceRetailLocationId')
            ->willReturn('source_retail_location_id');
        $this->rateQuoteRequest->expects($this->once())->method('getFedexAccountNumber')
            ->willReturn('fedex_account_number');
        $this->rateQuoteRequest->expects($this->exactly(2))->method('getFirstname')
            ->willReturn('firstname');
        $this->rateQuoteRequest->expects($this->exactly(2))->method('getLastname')
            ->willReturn('lastname');
        $this->rateQuoteRequest->expects($this->exactly(2))->method('getEmail')
            ->willReturn('email');
        $this->rateQuoteRequest->expects($this->exactly(2))->method('getTelephone')
            ->willReturn('telephone');
        $this->rateQuoteRequest->expects($this->once())->method('getProductAssociations')
            ->willReturn([["id" => 0, "quantity" => 5]]);
        $this->rateQuoteRequest->expects($this->once())->method('getProducts')
            ->willReturn([["catalogReference" => ["value"], "instanceId" => 0,"qty" => 5]]);
        $this->rateQuoteRequest->expects($this->once())->method('getShipmentId')
            ->willReturn('shipment_id');
    }
}
