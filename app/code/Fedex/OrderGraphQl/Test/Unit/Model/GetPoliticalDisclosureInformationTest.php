<?php

declare(strict_types=1);

namespace Fedex\OrderGraphQl\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Fedex\OrderGraphQl\Model\GetPoliticalDisclosureInformation;
use Fedex\PoliticalDisclosure\Api\OrderDisclosureRepositoryInterface;
use Fedex\PoliticalDisclosure\Model\OrderDisclosure;
use Magento\Sales\Model\Order;
use Magento\Directory\Model\RegionFactory;
use Magento\Directory\Model\Region;
use Fedex\CartGraphQl\Model\Region\RegionData;

class GetPoliticalDisclosureInformationTest extends TestCase
{
    public function testGetPoliticalDisclosureInfoReturnsDisclosureArray()
    {
        $orderId = 57341;
        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getId')->willReturn($orderId);

        $shippingAddressMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCountryId', 'getData'])
            ->getMock();
        $shippingAddressMock->method('getCountryId')->willReturn('US');
        $shippingAddressMock->method('getData')->with('company')->willReturn(null);
        $orderMock->method('getShippingAddress')->willReturn($shippingAddressMock);

        $disclosureMock = $this->getMockBuilder(OrderDisclosure::class)
            ->disableOriginalConstructor()
            ->addMethods([
                'getCandidatePacBallotIssue',
                'getElectionDate',
                'getSponsor',
                'getEmail',
                'getAddressStreetLines',
                'getCity',
                'getZipCode',
                'getDisclosureStatus',
                'getElectionStateId'
            ])
            ->getMock();

        $disclosureMock->method('getCandidatePacBallotIssue')->willReturn('Test Issue');
        $disclosureMock->method('getElectionDate')->willReturn('2024-11-05');
        $disclosureMock->method('getSponsor')->willReturn('Test Committee');
        $disclosureMock->method('getEmail')->willReturn('test@example.com');
        $disclosureMock->method('getAddressStreetLines')->willReturn("123 Main St\nSuite 100");
        $disclosureMock->method('getCity')->willReturn('Memphis');
        $disclosureMock->method('getZipCode')->willReturn('38116');
        $disclosureMock->method('getDisclosureStatus')->willReturn(true);
        $disclosureMock->method('getElectionStateId')->willReturn(184);

        $repositoryMock = $this->createMock(OrderDisclosureRepositoryInterface::class);
        $repositoryMock->method('getByOrderId')->with($orderId)->willReturn($disclosureMock);

        $regionMock = $this->getMockBuilder(Region::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCode'])
            ->onlyMethods(['load'])
            ->getMock();
        $regionMock->expects($this->any())
            ->method('getCode')
            ->willReturn('WA');
        $regionMock->expects($this->any())
            ->method('load')
            ->with(184)
            ->willReturn($regionMock);
        $regionMock->setData(['region_id' => 184]);
        $regionMock = new class($regionMock) extends Region {
            public function __construct($mock)
            {
                // Copy data from the mock to this instance
                foreach ($mock->getData() as $k => $v) {
                    $this->setData($k, $v);
                }
            }
            public function getId()
            {
                return 184;
            }
            public function getCode()
            {
                return 'WA';
            }
            public function load($id, $field = null)
            {
                return $this;
            }
        };
        $regionFactoryMock = $this->createMock(RegionFactory::class);
        $regionFactoryMock->method('create')->willReturn($regionMock);

        $regionDataMock = $this->createMock(RegionData::class);

        $model = new GetPoliticalDisclosureInformation($repositoryMock, $regionDataMock);

        $result = $model->getPoliticalDisclosureInfo($orderMock);

        $this->assertIsArray($result);
        $this->assertTrue($result['applicable']);
        $this->assertEquals(null, $result['description']);
        $this->assertEquals('2024-11-05', $result['eventDate']);
        $this->assertEquals('Test Committee', $result['sponsor']);
        $this->assertEquals('test@example.com', $result['customer']['emailAddress']);
        $this->assertEquals(['123 Main St', 'Suite 100'], $result['customer']['address']['streetLines']);
        $this->assertEquals('Memphis', $result['customer']['address']['city']);
        $this->assertEquals(null, $result['customer']['address']['stateOrProvinceCode']);
        $this->assertEquals('38116', $result['customer']['address']['postalCode']);
        $this->assertEquals('US', $result['customer']['address']['countryCode']);
        $this->assertEquals('HOME', $result['customer']['address']['addressClassification']);
    }

    public function testGetPoliticalDisclosureInfoReturnsNullIfNoDisclosure()
    {
        $orderId = 57341;
        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getId')->willReturn($orderId);

        $repositoryMock = $this->createMock(OrderDisclosureRepositoryInterface::class);
        $repositoryMock->method('getByOrderId')->with($orderId)->willReturn(null);

        $regionFactoryMock = $this->createMock(RegionFactory::class);

        $regionDataMock = $this->createMock(RegionData::class);

        $model = new GetPoliticalDisclosureInformation($repositoryMock, $regionDataMock);

        $result = $model->getPoliticalDisclosureInfo($orderMock);

        $this->assertNull($result);
    }
}
