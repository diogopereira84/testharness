<?php

namespace Fedex\PersonalAddressBook\Test\Unit\Helper;

use PHPUnit\Framework\TestCase;
use Fedex\PersonalAddressBook\Helper\Parties;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class PartiesTest extends TestCase
{
    public const POST_DATA = [
        'firstName' => 'John',
        'lastName' => 'Doe',
        'email' => 'test@test.com',
        'type' => 'MOBILE',
        'localNumber' => '1234567890',
        'companyName' => 'Acme Corp',
        'nickName' => 'Johnny',
        'streetLines' => '123 Main St',
        'city' => 'New York',
        'stateOrProvinceCode' => 'NY',
        'postalCode' => '10001',
        'countryCode' => 'US',
        'residential' => 'true',
        'opCoTypeCD' => 'XYZ',
        'ext' => '123'
    ];

    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var Parties
     */
    private $partiesHelper;

    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configInterface;

    /**
     * @var EncryptorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $encryptorInterface;

    /**
     * @var Curl|\PHPUnit\Framework\MockObject\MockObject
     */
    private $curl;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var CookieManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cookieManager;

    /**
     * @var ToggleConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    private $toggleConfig;

    protected function setUp(): void
    {
        $this->configInterface = $this->createMock(ScopeConfigInterface::class);
        $this->encryptorInterface = $this->createMock(EncryptorInterface::class);
        $this->curl = $this->createMock(Curl::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cookieManager = $this->createMock(CookieManagerInterface::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->contextMock = $this->getMockBuilder(\Magento\Framework\App\Helper\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager = new ObjectManager($this);
        $this->partiesHelper = $this->objectManager->getObject(
            Parties::class,
            [
                'context' => $this->contextMock,
                'configInterface' => $this->configInterface,
                'encryptorInterface' => $this->encryptorInterface,
                'curl' => $this->curl,
                'logger' => $this->logger,
                'cookieManager' => $this->cookieManager,
                'toggleConfig' => $this->toggleConfig
            ]
        );
    }

    public function testCallPostPartiesWithValidResponse()
    {
        $this->toggleConfig->method('getToggleConfigValue')
            ->willReturn(true);

        $response = [
            'output' => ['status' => 'success'],
            'errors' => null
        ];
        $this->curl->method('post')
            ->willReturn(true);
        $this->curl->method('getBody')
            ->willReturn(json_encode($response));

        $result = $this->partiesHelper->callPostParties(self::POST_DATA);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('output', $result);
        $this->assertEquals('success', $result['output']['status']);
    }

    public function testCallGetPartyFromAddressBookById()
    {
        $contactId = '12345';
        $this->toggleConfig->method('getToggleConfigValue')
            ->willReturn(true);
        $response = [
            'output' => ['contactId' => '12345', 'name' => 'John Doe'],
            'errors' => null
        ];
        $this->curl->method('post')
            ->willReturn(true);
        $this->curl->method('getBody')
            ->willReturn(json_encode($response));
        $result = $this->partiesHelper->callGetPartyFromAddressBookById($contactId);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('output', $result);
        $this->assertEquals('12345', $result['output']['contactId']);
    }

    public function testPrepareData()
    {
        $data = $this->partiesHelper->prepareData(self::POST_DATA);

        $this->assertNotEmpty($data);
    }

    public function testGetHeaders()
    {
        $fdxLogin = 'ssol6-las02.e5d5.b59c3f8050c5ca4ac3bacf7fbd9e38da';
        $authenticationToken = 'l7c481a55309cf4cb8929ffb020eb8922a';
        $this->cookieManager->expects($this->once())
            ->method('getCookie')
            ->with('fdx_login')
            ->willReturn($fdxLogin);
        $this->encryptorInterface->expects($this->once())
            ->method('decrypt')
            ->willReturn($authenticationToken);
        $result = $this->partiesHelper->getHeaders();

        $expectedHeaders = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $authenticationToken,
            "Cookie: fdx_login=" . $fdxLogin
        ];

        $this->assertEquals($expectedHeaders, $result);
    }

    public function testGetParams()
    {
        $expectedParams = [
            'summary' => 'complete',
            'partytype' => 'RECIPIENT',
            'addressbooktype' => 'CENTRAL',
            'countrycode' => 'US'
        ];
        $params = $this->partiesHelper->getParams();
        $this->assertEquals($expectedParams, $params);
    }

    public function testCallGetPartiesList()
    {
        $this->toggleConfig->method('getToggleConfigValue')
            ->willReturn(true);

        $response = [
                "transactionId" => "552f4414-a98a-408d-83ee-fcdc8bd845bc",
                "output" => [
                    "partyList" => [
                        [
                            "contactID" => 1115024718,
                            "nickName" => "Dev",
                            "contactName" => "Devs AdminPK",
                            "companyName" => "Infogain",
                            "countryCD" => "US",
                            "firstName" => "Devs",
                            "lastName" => "Admin",
                            "address" => [
                                "streetLines" => ["8287 Legacy Drive"],
                                "city" => "Plano",
                                "stateOrProvinceCode" => "TX",
                                "postalCode" => "75024",
                                "countryCode" => "US",
                                "residential" => false,
                            ],
                            "city" => "Plano",
                            "countryName" => "United States",
                            "addressType" => "RECIPIENT",
                            "addressBookType" => "PERSONAL",
                        ],
                        [
                            "contactID" => 1115024745,
                            "nickName" => "PallaviK",
                            "contactName" => "Devs Admin",
                            "countryCD" => "US",
                            "firstName" => "Devs",
                            "lastName" => "Admin",
                            "address" => [
                                "streetLines" => ["8287 Legacy Drive"],
                                "city" => "Plano",
                                "stateOrProvinceCode" => "TX",
                                "postalCode" => "75024",
                                "countryCode" => "US",
                                "residential" => false,
                            ],
                            "city" => "Plano",
                            "countryName" => "United States",
                            "addressType" => "RECIPIENT",
                            "addressBookType" => "PERSONAL",
                        ],
                        [
                            "contactID" => 1115024750,
                            "nickName" => "Devs",
                            "contactName" => "Devs Admin",
                            "companyName" => "Prod162",
                            "countryCD" => "US",
                            "firstName" => "Devs",
                            "lastName" => "Admin",
                            "address" => [
                                "streetLines" => ["8287 Legacy Drive"],
                                "city" => "Plano",
                                "stateOrProvinceCode" => "TX",
                                "postalCode" => "75024",
                                "countryCode" => "US",
                                "residential" => false,
                            ],
                            "city" => "Plano",
                            "countryName" => "United States",
                            "addressType" => "RECIPIENT",
                            "addressBookType" => "PERSONAL",
                        ],
                    ],
                    "exceededMaxReturnLimit" => false,
                    "totalNumberOfRecords" => 3,
                    "recordsPerPage" => 3,
                ],
            ];
        $output = [
                    "partyList" => [
                        [
                            "contactID" => 1115024718,
                            "nickName" => "Dev",
                            "contactName" => "Devs AdminPK",
                            "companyName" => "Infogain",
                            "countryCD" => "US",
                            "firstName" => "Devs",
                            "lastName" => "Admin",
                            "address" => [
                                "streetLines" => ["8287 Legacy Drive"],
                                "city" => "Plano",
                                "stateOrProvinceCode" => "TX",
                                "postalCode" => "75024",
                                "countryCode" => "US",
                                "residential" => false,
                            ],
                            "city" => "Plano",
                            "countryName" => "United States",
                            "addressType" => "RECIPIENT",
                            "addressBookType" => "PERSONAL",
                        ],
                        [
                            "contactID" => 1115024745,
                            "nickName" => "PallaviK",
                            "contactName" => "Devs Admin",
                            "countryCD" => "US",
                            "firstName" => "Devs",
                            "lastName" => "Admin",
                            "address" => [
                                "streetLines" => ["8287 Legacy Drive"],
                                "city" => "Plano",
                                "stateOrProvinceCode" => "TX",
                                "postalCode" => "75024",
                                "countryCode" => "US",
                                "residential" => false,
                            ],
                            "city" => "Plano",
                            "countryName" => "United States",
                            "addressType" => "RECIPIENT",
                            "addressBookType" => "PERSONAL",
                        ],
                        [
                            "contactID" => 1115024750,
                            "nickName" => "Devs",
                            "contactName" => "Devs Admin",
                            "companyName" => "Prod162",
                            "countryCD" => "US",
                            "firstName" => "Devs",
                            "lastName" => "Admin",
                            "address" => [
                                "streetLines" => ["8287 Legacy Drive"],
                                "city" => "Plano",
                                "stateOrProvinceCode" => "TX",
                                "postalCode" => "75024",
                                "countryCode" => "US",
                                "residential" => false,
                            ],
                            "city" => "Plano",
                            "countryName" => "United States",
                            "addressType" => "RECIPIENT",
                            "addressBookType" => "PERSONAL",
                        ],
                    ],
                    "exceededMaxReturnLimit" => false,
                    "totalNumberOfRecords" => 3,
                    "recordsPerPage" => 3,
                ];
        $this->curl->method('post')
            ->willReturn(true);
        $this->curl->method('getBody')
            ->willReturn(json_encode($response));
        $result = $this->partiesHelper->callGetPartiesList();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('output', $result);
        $this->assertEquals($output, $result['output']);
    }

    public function testCallPutPartiesWithValidResponse()
    {
        $contactId = 1115024718 ;

        $this->toggleConfig->method('getToggleConfigValue')
            ->willReturn(true);

        $response = [
            'output' => ['status' => 'success'],
            'errors' => null
        ];
        $this->curl->method('post')
            ->willReturn(true);
        $this->curl->method('getBody')
            ->willReturn(json_encode($response));

        $result = $this->partiesHelper->callPutParties($contactId, self::POST_DATA);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('output', $result);
        $this->assertEquals('success', $result['output']['status']);
    }

    public function testCallPutPartiesWithException()
    {
        $contactId = 1115024718;
        $exception = new NoSuchEntityException();

        $this->toggleConfig->method('getToggleConfigValue')
            ->willReturn(true);

        $response = [
            'output' => ['status' => 'success'],
            'errors' => null
        ];
        $this->curl->method('post')
             ->willReturn(true);
            
        $this->curl->method('getBody')
            ->willReturn(json_encode($response))->willThrowException($exception);

        $result = $this->partiesHelper->callPutParties($contactId, self::POST_DATA);

        $this->assertNull($result);
    }

    public function testCallDeletePartyFromAddressBookById()
    {
        $contactId = '12345';
        $this->toggleConfig->method('getToggleConfigValue')
            ->willReturn(true);
        $response = [
            'output' => ['contactId' => '12345', 'name' => 'John Doe'],
            'errors' => null
        ];
        $this->curl->method('post')
            ->willReturn(true);
        $this->curl->method('getBody')
            ->willReturn(json_encode($response));
        $result = $this->partiesHelper->callDeletePartyFromAddressBookById($contactId);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('output', $result);
        $this->assertEquals('12345', $result['output']['contactId']);
    }

    public function testCallDeletePartyFromAddressBookByIdWithError()
    {
        $contactId = '12345';
        $this->toggleConfig->method('getToggleConfigValue')
            ->willReturn(true);
        $response = [
            'errors' => ['contactId' => '12345', 'name' => 'John Doe']
        ];
        $this->curl->method('post')
            ->willReturn(true);
        $this->curl->method('getBody')
            ->willReturn(json_encode($response));
        $this->assertNotNull($this->partiesHelper->callDeletePartyFromAddressBookById($contactId));
    }

    public function testCallDeletePartyFromAddressBookByIdWithException()
    {
        $contactId = '12345';
        $this->toggleConfig->method('getToggleConfigValue')
            ->willReturn(true);
        $this->curl->method('post')
            ->willThrowException(new \Exception());
        $this->assertNull($this->partiesHelper->callDeletePartyFromAddressBookById($contactId));
    }

    public function testPaginatedDataWithEmptyData()
    {
        $result = $this->partiesHelper->paginatedData([]);
        $this->assertEquals([], $result, "Expected empty array for empty input data");
    }

    public function testPaginatedDataWithLessDataThanPageSize()
    {
        $data = [1, 2, 3];
        $result = $this->partiesHelper->paginatedData($data);
        $this->assertEquals([1, 2, 3], $result, "Expected same data when number of items is less than page size");
    }
}
