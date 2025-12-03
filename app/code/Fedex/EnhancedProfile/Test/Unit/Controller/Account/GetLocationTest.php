<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Test\Unit\Controller\Account;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Action\Context;
use Fedex\Shipto\Helper\Data;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\EnhancedProfile\Controller\Account\GetLocation;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Model\Session;

/**
 * Unit tests for get location
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetLocationTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Action\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $dataMock;
    protected $jsonFactoryMock;
    protected $jsonMock;
    protected $requestMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $getLocationMock;
    /**
     * @var Session
     */
    protected $toggleConfigMock;
    protected $customerSession;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->dataMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        
        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();
            
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->toggleConfigMock = $this->getMockBuilder(\Fedex\EnvironmentManager\ViewModel\ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomer', 'getEmail'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        
        $this->getLocationMock = $this->objectManager->getObject(
            GetLocation::class,
            [
                'context' => $this->contextMock,
                'data' => $this->dataMock,
                'jsonFactory' => $this->jsonFactoryMock,
                'request' => $this->requestMock,
                'toggleConfig' => $this->toggleConfigMock,
                'customerSession' => $this->customerSession
            ]
        );
    }
    
    /**
     * Test execute method
     *
     */
    public function testExecute()
    {
        
        $data = [];
        $data['zipcode'] = '10002';
        $responseData['success'] = "1";
        $responseData['locations'] = $this->getLocationData();
        $this->requestMock->expects($this->any())->method('getParams')->willReturn($data);
        $this->customerSession->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getEmail')->willReturn('test@gmail.com');
        $this->dataMock->expects($this->any())->method('getAllLocationsByZip')->willReturn($responseData);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();
        
        $this->assertEquals($this->jsonMock, $this->getLocationMock->execute());
    }

    /**
     * Test execute method without param.
     *
     */
    public function testExecuteWithoutParam()
    {
        $data = [];
        $this->requestMock->expects($this->any())->method('getParams')->willReturn($data);
        
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();
        
        $this->assertEquals($this->jsonMock, $this->getLocationMock->execute());
    }
    
    /**
     * Test execute method without location response.
     *
     */
    public function testExecuteWithoutLocationResponse()
    {
        $data = [];
        $data['zipcode'] = '10002';
        $responseData['success'] = "1";
        $responseData['locations'] = $this->getLocationData();
        $this->requestMock->expects($this->any())->method('getParams')->willReturn($data);
        $this->dataMock->expects($this->any())->method('getAllLocationsByZip')->willReturn([]);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();
        
        $this->assertEquals($this->jsonMock, $this->getLocationMock->execute());
    }
    
    /**
     * Get location data
     * .
     * @return array
     */
    public function getLocationData()
    {
        $locationData = [];
        $locationData[0]["Id"] = "0883";
        $locationData[0]["address"]["address1"] = "110 William St";
        $locationData[0]["address"]["address2"] = "";
        $locationData[0]["address"]["city"] = "New York";
        $locationData[0]["address"]["stateOrProvinceCode"] = "NY";
        $locationData[0]["address"]["postalCode"] ="10038";
        $locationData[0]["address"]["countryCode"] = "US";
        $locationData[0]["address"]["addressType"] = "";
        $locationData[0]["name"] = "New York NY William Street";
        $locationData[0]["phone"] = "2127664646";
        $locationData[0]["email"] = "usa0883@fedex.com";
        $locationData[0]["locationType"] = "OFFICE_PRINT";
        $locationData[0]["available"] = true;
        $locationData[0]["availabilityReason"] = "AVAILABLE";
        $locationData[0]["pickupEnabled"] = true;
        $locationData[0]["geoCode"]["latitude"] = "40.708973";
        $locationData[0]["geoCode"]["longitude"] = "-74.00702";
        $locationData[0]["services"][] = "POS Active,Copy and Print,JetLite";
        $locationData[0]["hoursOfOperation"][] = ["date" => "Nov 28, 2021 12:00:00 AM",
                                                "day" => "SUNDAY","schedule" => "Closed"];
        return $locationData;
    }

    /**
     * Test execute method with toggle on and locationId exist.
     *
     */
    public function testExecuteWithToggleOnAndLocationIdExist(): void
    {
        $data = ['zipcode' => '10038'];
        $dataAllocationResponse = [
            ['locationId' => '0883', 'name' => 'New York NY']
        ];

        $this->requestMock->expects($this->any())->method('getParams')->willReturn($data);
        $this->dataMock->expects($this->any())->method('getAllLocationsByZip')->willReturn($dataAllocationResponse);
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->with('tech_titans_d_217639')
            ->willReturn(true);

        $this->customerSession->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getEmail')->willReturn('test@gmail.com');

        $expectedResponse = ['locations' => $dataAllocationResponse];
        $this->jsonFactoryMock->expects($this->once())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->once())->method('setData')->with($expectedResponse)->willReturnSelf();

        $this->assertEquals($this->jsonMock, $this->getLocationMock->execute());
    }

    /**
     * Test execute method with toggle on and locationId missing.
     *
     */
    public function testExecuteWithToggleOnAndLocationIdMissing(): void
    {
        $data = ['zipcode' => '10038'];
        $dataAllocationResponse = [
            ['name' => 'New York NY']
        ];

        $this->requestMock->expects($this->any())->method('getParams')->willReturn($data);
        $this->dataMock->expects($this->any())->method('getAllLocationsByZip')->willReturn($dataAllocationResponse);
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->with('tech_titans_d_217639')
            ->willReturn(true);

        $this->customerSession->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getEmail')->willReturn('test@gmail.com');

        $expectedResponse = [
            'status' => GetLocation::ERROR,
            'noLocation' => 1,
            'message' => GetLocation::SYSTEM_ERROR
        ];

        $this->jsonFactoryMock->expects($this->once())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->once())->method('setData')->with($expectedResponse)->willReturnSelf();

        $this->assertEquals($this->jsonMock, $this->getLocationMock->execute());
    }

}