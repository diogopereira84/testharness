<?php

namespace Fedex\Company\Test\Unit\Controller\Adminhtml\Plocation;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Psr\Log\LoggerInterface as Logger;
use Fedex\Shipto\Model\ProductionLocationFactory;
use Fedex\Shipto\Model\ProductionLocation;
use Fedex\Shipto\Model\ResourceModel\ProductionLocation\Collection;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\Shipto\Controller\Adminhtml\Plocation\Save;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Customer\Model\Session;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;


/**
 * Unit tests for adminhtml company save controller.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveTest extends TestCase
{
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $productionLocationFactoryMock;
    protected $productionLocationMock;
    protected $productionLocationCollection;
    protected $jsonFactoryMock;
    protected $jsonMock;
    protected $serializer;
    protected $customerSession;
    protected $requestMock;
    protected $toogleConfigMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $saveMock;
    private const REQUEST_DATA = [
        'location_id' => '0883',
        'company_id' => '2',
        'is_recommended_store'=> 'recommended_stores_all_location',
        'is_restricted_product_location_toggle' => 1
    ];

    private const REQUEST_DATA_LOCATION_TOGGLE_OFF = [
        'id' => '0883',
        'company_id' => '2',
        'is_recommended_store'=> 'recommended_stores_all_location',
        'is_restricted_product_location_toggle' => 0
    ];

    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productionLocationFactoryMock = $this->getMockBuilder(ProductionLocationFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->productionLocationMock = $this->getMockBuilder(ProductionLocation::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData', 'load', 'save', 'delete', 'getCollection','addFieldToFilter'])
            ->getMock();

        $this->productionLocationCollection = $this->createMock(Collection::class);

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
            ->setMethods(['getAllLocations','setAllLocations'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->toogleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->saveMock = $this->objectManager->getObject(
            Save::class,
            [
                'logger' => $this->loggerMock,
                'productionLocationFactory' => $this->productionLocationFactoryMock,
                'jsonFactory' => $this->jsonFactoryMock,
                'serializer' => $this->serializer,
                'customerSession' => $this->customerSession,
                '_request' => $this->requestMock,
                'toggleConfig'=> $this->toogleConfigMock
            ]
        );
    }

    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $data = ['location_id' => '2', 'company_id' => '2'];
        $this->requestMock->expects($this->any())->method('getParams')->willReturn($data);
        $this->productionLocationFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->productionLocationMock);
        $this->productionLocationMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->productionLocationMock->expects($this->any())->method('save')->willReturnSelf();

        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertEquals($this->jsonMock, $this->saveMock->execute());
    }

    public function testExecuteWithLocationSession()
    {
        $locationData = $this->getLocationData();

        $locationDataJason = json_encode($locationData);
        $this->requestMock->expects($this->any())->method('getParams')
            ->willReturn(static::REQUEST_DATA_LOCATION_TOGGLE_OFF);
        $this->customerSession->expects($this->any())->method('getAllLocations')
            ->willReturn($locationDataJason);

        $this->serializer->expects($this->any())->method('unserialize')
            ->with($locationDataJason)->willReturn(json_decode($locationDataJason, true));

        $this->productionLocationFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->productionLocationMock);

        $this->productionLocationMock->expects($this->any())->method('getCollection')
            ->will($this->returnValue($this->productionLocationCollection));

        $this->productionLocationCollection->expects($this->any())->method('addFieldToFilter')
            ->will($this->returnSelf());

        $this->productionLocationMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->productionLocationMock->expects($this->any())->method('save')->willReturnSelf();

        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();


        $this->assertEquals($this->jsonMock, $this->saveMock->execute());
    }

    public function testExecuteWithLocationSessionwithTogglOn()
    {
        $locationData = $this->getLocationData();
        $data = [
            'key' => '87078ea8a1ea6417ec2266605ce66da263b20ae2d25f651f43a91b4fcdd1d9a1',
            'isAjax' => true,
            'location_id' => 1966,
            'company_id' => 23,
            'is_recommended_store' => 'recommended_stores_all_location',
            'is_restricted_product_location_toggle' => 1,
            'form_key' => 'jaBENSe9zjTm4FXp'
        ];

        $locationDataJason = json_encode($locationData);
        $this->requestMock->expects($this->any())->method('getParams')
            ->willReturn($data);
        $this->customerSession->expects($this->any())->method('getAllLocations')
            ->willReturn($locationDataJason);

        $this->serializer->expects($this->any())->method('unserialize')
            ->with($locationDataJason)->willReturn(json_decode($locationDataJason, true));

        $this->productionLocationFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->productionLocationMock);

        $this->productionLocationMock->expects($this->any())->method('getCollection')
            ->will($this->returnValue($this->productionLocationCollection));

        $this->productionLocationCollection->expects($this->any())->method('addFieldToFilter')
            ->will($this->returnSelf());

        $this->productionLocationMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->productionLocationMock->expects($this->any())->method('save')->willReturnSelf();

        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('setAllLocations')->willReturnSelf();
        $this->toogleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->assertEquals($this->jsonMock, $this->saveMock->execute());
    }

    public function testExecuteWithExistingLocation()
    {
        $locationData = $this->getLocationData();

        $locationDataJason = json_encode($locationData);
        $this->requestMock->expects($this->any())->method('getParams')
            ->willReturn(static::REQUEST_DATA_LOCATION_TOGGLE_OFF);
        $this->customerSession->expects($this->any())->method('getAllLocations')
            ->willReturn($locationDataJason);

        $this->serializer->expects($this->any())->method('unserialize')->with($locationDataJason)
            ->willReturn(json_decode($locationDataJason, true));

        $this->productionLocationFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->productionLocationMock);

        $this->productionLocationMock->expects($this->any())->method('getCollection')
            ->will($this->returnValue($this->productionLocationCollection));

        $this->productionLocationCollection->expects($this->any())->method('addFieldToFilter')
            ->will($this->returnSelf());
        $this->productionLocationCollection->expects($this->any())->method('getSize')->willReturn(1);

        $this->productionLocationMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->productionLocationMock->expects($this->any())->method('save')->willReturnSelf();

        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertEquals($this->jsonMock, $this->saveMock->execute());
    }

    public function testExecuteWithoutParamater()
    {
        $data = ['location_id' => '', 'company_id' => '2','is_recommended_store' => 'recommended_stores_all_location'];

        $locationData = $this->getLocationData();

        $locationDataJason = json_encode($locationData);
        $this->requestMock->expects($this->any())->method('getParams')->willReturn($data);
        $this->customerSession->expects($this->any())->method('getAllLocations')
            ->willReturn($locationDataJason);

        $this->serializer->expects($this->any())->method('unserialize')->with($locationDataJason)
            ->willReturn(json_decode($locationDataJason, true));

        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertEquals($this->jsonMock, $this->saveMock->execute());
    }

    /**
     * Test for execute method withException.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $phrase = new Phrase(__('Something went wrong. Please try again later.'));
        $exception = new \Exception($phrase);

        $locationData = $this->getLocationData();

        $locationDataJason = json_encode($locationData);
        $this->requestMock->expects($this->any())->method('getParams')
            ->willReturn(static::REQUEST_DATA_LOCATION_TOGGLE_OFF);
        $this->customerSession->expects($this->any())->method('getAllLocations')
            ->willReturn($locationDataJason);

        $this->serializer->expects($this->any())->method('unserialize')->with($locationDataJason)
            ->willReturn(json_decode($locationDataJason, true));

        $this->productionLocationFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->productionLocationMock);

        $this->productionLocationMock->expects($this->any())->method('getCollection')
            ->will($this->returnValue($this->productionLocationCollection));

        $this->productionLocationCollection->expects($this->any())->method('addFieldToFilter')
            ->will($this->returnSelf());
        $this->productionLocationCollection->expects($this->any())->method('getSize')->willReturn(0);

        $this->productionLocationMock->expects($this->any())->method('setData')
            ->willThrowException($exception);
        $this->productionLocationMock->expects($this->any())->method('save')->willReturnSelf();

        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertEquals($this->jsonMock, $this->saveMock->execute());
    }

    public function getLocationData()
    {
        $locationData = [];

        $locationData["0883"]["Id"] = "0883";
        $locationData["0883"]["address"]["address1"] = "110 William St";
        $locationData["0883"]["address"]["address2"] = "";
        $locationData["0883"]["address"]["city"] = "New York";
        $locationData["0883"]["address"]["stateOrProvinceCode"] = "NY";
        $locationData["0883"]["address"]["postalCode"] ="10038";
        $locationData["0883"]["address"]["countryCode"] = "US";
        $locationData["0883"]["address"]["addressType"] = "";

        $locationData["0883"]["name"] = "New York NY William Street";
        $locationData["0883"]["phone"] = "2127664646";
        $locationData["0883"]["email"] = "usa0883@fedex.com";
        $locationData["0883"]["locationType"] = "OFFICE_PRINT";
        $locationData["0883"]["available"] = true;
        $locationData["0883"]["availabilityReason"] = "AVAILABLE";
        $locationData["0883"]["pickupEnabled"] = true;
        $locationData["0883"]["geoCode"]["latitude"] = "40.708973";
        $locationData["0883"]["geoCode"]["longitude"] = "-74.00702";
        $locationData["0883"]["services"][] = "POS Active,Copy and Print,JetLite";
        $locationData["0883"]["hoursOfOperation"][] = ["date" => "Nov 28, 2021 12:00:00 AM",
                                                "day" => "SUNDAY","schedule" => "Closed"];

        return $locationData;
    }

    /**
     * test method for prepareKeyArray
     */
    public function testPrepareKeyArray()
    {
        $locationIds = [0 => '0883'];
        $getAllLocationsFromSession = $this->getLocationData();

        $this->assertNotNull($this->saveMock->prepareKeyArray($locationIds, $getAllLocationsFromSession));
    }

    public function testPrepareArray()
    {
        $locations = [
            'officeLocationId' => 1,
            'locationName' => 'Office Location',
            'address' => [
                'streetLines' => ['123 Main St', 'Suite 100'],
                'city' => 'New York',
                'stateOrProvinceCode' => 'NY',
                'countryCode' => 'US',
                'postalCode' => '10001',
            ],
            'phoneNumber' => '123-456-7890',
            'emailAddress' => 'office@example.com',
            'geoCode' => [
                'latitude' => 40.7128,
                'longitude' => -74.0060,
            ],
        ];

        $resultToggleTrue = $this->saveMock->prepareArray($locations, static::REQUEST_DATA);

        $this->assertEquals(1, $resultToggleTrue['location_id']);
        $this->assertEquals('Office Location', $resultToggleTrue['location_name']);
        $this->assertEquals(2, $resultToggleTrue['company_id']);
        $this->assertEquals('123 Main St', $resultToggleTrue['address1']);
        $this->assertEquals('Suite 100', $resultToggleTrue['address2']);
        $this->assertEquals('New York', $resultToggleTrue['city']);
        $this->assertEquals('NY', $resultToggleTrue['state']);
        $this->assertEquals('US', $resultToggleTrue['country_id']);
        $this->assertEquals('10001', $resultToggleTrue['postcode']);
        $this->assertEquals('123-456-7890', $resultToggleTrue['telephone']);
        $this->assertEquals('office@example.com', $resultToggleTrue['location_email']);
        $this->assertEquals(40.7128, $resultToggleTrue['lat']);
        $this->assertEquals(-74.0060, $resultToggleTrue['long']);

        $locations = [
            'Id' => 1,
            'name' => 'Office Location',
            'address' => [
                'address1' => '123 Main St',
                'address2' => 'Suite 100',
                'city' => 'New York',
                'stateOrProvinceCode' => 'NY',
                'countryCode' => 'US',
                'postalCode' => '10001',
                'addressType' =>  ''
            ],
            'phone' => '123-456-7890',
            'email' => 'office@example.com',
            "locationType" => "OFFICE_PRINT",
            "available" => true,
            "availabilityReason" => "AVAILABLE",
            "pickupEnabled" => true,
            'geoCode' => [
                'latitude' => 40.7128,
                'longitude' => -74.0060,
            ],
            "services" => [
                "Print Online (POL)",
                "FedEx Consolidated Returns",
                "Signs & Graphics",
                "FedEx Ground Service",
                "POL Commercial",
                "Copy and Print",
                "DocStore",
                "Passport Pictures",
                "Pick Up",
                "FedEx Express Service",
                "FedEx Pack & Ship",
                "Express Pay",
                "Ground Holds",
                "Order to Pay",
                "Located on www",
                "Ground Hold Default",
                "Self-Service Pay",
                "WebOrder",
                "FPOS Active",
                "PUD",
                "ACTIVE_CWKR_FLAG"
            ],
            "hoursOfOperation" => [
                [
                  "date" => "Mar 24, 2024 12:00:00 AM",
                  "day"=> "SUNDAY",
                  "schedule"=> "Closed"
                ],
                [
                  "date"=>"Mar 25, 2024 12:00:00 AM",
                  "day"=> "MONDAY",
                  "schedule"=> "Open",
                  "openTime"=> "9:00 AM",
                  "closeTime"=>"8:00 PM"
                ]
              ]
        ];

        $resultToggleFalse = $this->saveMock->prepareArray($locations, static::REQUEST_DATA_LOCATION_TOGGLE_OFF);

        $this->assertEquals(1, $resultToggleFalse['location_id']);
        $this->assertEquals('Office Location', $resultToggleFalse['location_name']);
        $this->assertEquals(2, $resultToggleFalse['company_id']);
        $this->assertEquals('123 Main St', $resultToggleFalse['address1']);
        $this->assertEquals('Suite 100', $resultToggleFalse['address2']);
        $this->assertEquals('New York', $resultToggleFalse['city']);
        $this->assertEquals('NY', $resultToggleFalse['state']);
        $this->assertEquals('US', $resultToggleFalse['country_id']);
        $this->assertEquals('10001', $resultToggleFalse['postcode']);
        $this->assertEquals('123-456-7890', $resultToggleFalse['telephone']);
        $this->assertEquals('office@example.com', $resultToggleFalse['location_email']);
        $this->assertEquals(40.7128, $resultToggleFalse['lat']);
        $this->assertEquals(-74.0060, $resultToggleFalse['long']);
    }

    public function testPrepareHoursOfOperationsArray()
    {
        $locations = [
            'operatingHours' => [
                ['dayOfWeek' => 'Mon', 'startTime' => '08:00', 'endTime' => '17:00'],
                ['dayOfWeek' => 'Tue', 'startTime' => '09:00', 'endTime' => '18:00'],
            ]
        ];

        $expectedFormattedHoursOfOperations = [
            ['day' => 'Mon', 'startTime' => '08:00', 'endTime' => '17:00'],
            ['day' => 'Tue', 'startTime' => '09:00', 'endTime' => '18:00'],
        ];
        $result = $this->saveMock->prepareHoursOfOperationsArray($locations);
        $this->assertNotNull(json_encode($expectedFormattedHoursOfOperations));
    }

    /**
     * Test method for handleLocationRemoval
     */
    public function testHandleLocationRemoval()
    {
        $this->productionLocationFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->productionLocationMock);

        $this->productionLocationMock->expects($this->once())->method('getCollection')
            ->will($this->returnValue($this->productionLocationCollection));

        $this->productionLocationCollection->expects($this->any())->method('addFieldToFilter')
            ->will($this->returnSelf());
        $this->productionLocationCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->productionLocationCollection->expects($this->any())->method('getData')->willReturn([['id' => 1]]);
        $this->productionLocationMock->expects($this->any())->method('load')->willReturnSelf();
        $this->productionLocationMock->expects($this->any())->method('delete')->willReturnSelf();

        $this->assertNull(
            $this->saveMock->handleLocationRemoval($this->productionLocationMock, static::REQUEST_DATA)
        );
    }

    /**
     * Test for handleLocationRemoval method with Exception.
     *
     * @return void
     */
    public function testHandleLocationRemovalWithException()
    {
        $phrase = new Phrase(__('Error occured while removing production locations for the company id.'));
        $exception = new \Exception($phrase);

        $this->productionLocationFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->productionLocationMock);

        $this->productionLocationMock->expects($this->once())->method('getCollection')
            ->willThrowException($exception);

        $this->assertNull($this->saveMock->handleLocationRemoval($this->productionLocationMock, static::REQUEST_DATA));
    }
}
