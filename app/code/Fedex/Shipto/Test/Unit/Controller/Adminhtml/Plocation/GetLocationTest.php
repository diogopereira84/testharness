<?php

namespace Fedex\Company\Test\Unit\Controller\Adminhtml\Plocation;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\View\Result\PageFactory;
use Fedex\Shipto\Model\ProductionLocationFactory;
use Fedex\Shipto\Model\ProductionLocation;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\Shipto\Controller\Adminhtml\Plocation\GetLocation;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Customer\Model\Session;
use Fedex\Shipto\Helper\Data;

/**
 * Unit tests for adminhtml company save controller.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetLocationTest extends TestCase
{
    protected $dataMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var (\Magento\Framework\View\Result\PageFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $pageFactoryMock;
    /**
     * @var (\Fedex\Shipto\Model\ProductionLocationFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $productionLocationFactoryMock;
    /**
     * @var (\Fedex\Shipto\Model\ProductionLocation & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $productionLocationMock;
    protected $jsonFactoryMock;
    protected $jsonMock;
    /**
     * @var (\Magento\Framework\Serialize\SerializerInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $serializer;
    /**
     * @var (\Magento\Customer\Model\Session & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $customerSession;
    protected $requestMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $getLocationMock;
    protected function setUp(): void
    {
        $this->dataMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageFactoryMock = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productionLocationFactoryMock = $this->getMockBuilder(ProductionLocationFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->productionLocationMock = $this->getMockBuilder(ProductionLocation::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData','save','getCollection','addFieldToFilter'])
            ->getMock();

        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();

        $this->serializer = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllLocations'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->getLocationMock = $this->objectManager->getObject(
            GetLocation::class,
            [
                'logger' => $this->loggerMock,
                'pageFactory' => $this->pageFactoryMock,
                'data' => $this->dataMock,
                'jsonFactory' => $this->jsonFactoryMock,
                'serializer' => $this->serializer,
                'customerSession' => $this->customerSession,
                '_request' => $this->requestMock
            ]
        );
    }

    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecuteWithoutParam()
    {

        $data = [];
        $this->requestMock->expects($this->any())->method('getParams')->willReturn($data);

        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertEquals($this->jsonMock, $this->getLocationMock->execute());
    }
    public function testExecute()
    {

        $data = [];
        $data['zipcode'] = '10002';
        $responseData['success'] = "1";
        $responseData['locations'] = $this->getLocationData();
        $this->requestMock->expects($this->any())->method('getParams')->willReturn($data);
        $this->dataMock->expects($this->any())->method('getAllLocationsByZip')->willReturn($responseData);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertEquals($this->jsonMock, $this->getLocationMock->execute());
    }

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
}
