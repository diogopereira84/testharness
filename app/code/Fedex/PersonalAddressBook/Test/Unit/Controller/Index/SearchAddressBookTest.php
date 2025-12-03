<?php
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\PersonalAddressBook\Controller\Index\SearchAddressBook;
use Magento\Framework\App\Action\Context;
use Fedex\PersonalAddressBook\Helper\Parties;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;

class SearchAddressBookTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Action\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    /**
     * @var (\Fedex\PersonalAddressBook\Helper\Parties & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $partiesHelperMock;
    protected $customerSessionMock;
    protected $toggleConfig;
    protected $jsonFactoryMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $requestMock;
    protected $searchAddressBook;
    public const PERSONAL_ADDRESS_STREET = 'personal_address_address';
    public const PERSONAL_ADDRESS_CITY = 'personal_address_city';
    public const PERSONAL_ADDRESS_STATE = 'personal_address_state';
    public const PERSONAL_ADDRESS_ZIP = 'personal_address_zip';

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->partiesHelperMock = $this->createMock(Parties::class);
        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->setMethods(['getPartiesList','setPartiesList','getAddressBookPageSize'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->jsonFactoryMock = $this->createMock(JsonFactory::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPostValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        // Creating an instance of the SearchAddressBook controller
        $this->searchAddressBook = new SearchAddressBook(
            $this->loggerMock,
            $this->requestMock,
            $this->toggleConfig,
            $this->jsonFactoryMock,
            $this->partiesHelperMock,
            $this->customerSessionMock,
            $this->contextMock
        );
    }

    public function testExecuteWithSearchField()
    {
        $this->toggleConfig->method('getToggleConfigValue')
            ->willReturn(true);
        // Mock request with searchField filter parameters
        $this->requestMock->expects($this->any())
            ->method('getPostValue')
            ->willReturn([
                'addressSearchOption' => 'firstName',
                'searchField' => 'FA'
            ]);

        // Mock parties list
        $partiesList = [
            ['firstName' => 'Alice'],
            ['firstName' => 'Bob'],
            ['firstName' => 'Anna']
        ];

        $this->customerSessionMock->method('getPartiesList')
            ->willReturn(json_encode($partiesList));

        $this->partiesHelperMock->method('paginatedData')->willReturn($partiesList);

        // Mock result Json
        $resultJsonMock = $this->createMock(\Magento\Framework\Controller\Result\Json::class);
        $this->jsonFactoryMock->method('create')
            ->willReturn($resultJsonMock);

        // Call the execute method
        $this->searchAddressBook->execute();
    }

    public function testExecuteWithException()
    {
        // Mock request to throw an exception
        $this->requestMock->expects($this->any())
            ->method('getPostValue')
            ->willThrowException(new \Exception());

        // Mock result Json
        $resultJsonMock = $this->createMock(\Magento\Framework\Controller\Result\Json::class);
        $this->jsonFactoryMock->method('create')
            ->willReturn($resultJsonMock);

        // Call the execute method
        $this->assertNull($this->searchAddressBook->execute());
    }

    public function testSearchPersonalAddressList()
    {
        $partiesList =
        [
           [
                'firstName' => 'Alice',
                'address' =>[
                    'streetLines' => [
                            '0' => '8200 Oakwood',
                            '1' => '4th Cross'
                    ],
                    'city' => 'Plano',
                    'stateOrProvinceCode' => 'TX',
                    'postalCode' => '75024',
                    'countryCode' => 'US',
                    'residential' => '1'
                ]
           ]
        ];

        // Test when searching for 'A'
        $result = $this->searchAddressBook->searchPersonalAddressList('A', 'firstName', $partiesList);
        $this->assertNotNull($result);

        // Test when searching for 'ADDRESS_STREET'
        $result = $this->searchAddressBook->searchPersonalAddressList('8', self::PERSONAL_ADDRESS_STREET, $partiesList);
        $this->assertNotNull($result);

        // Test when searching for 'ADDRESS_CITY'
        $result = $this->searchAddressBook->searchPersonalAddressList('P', self::PERSONAL_ADDRESS_CITY, $partiesList);
        $this->assertNotNull($result);

        // Test when searching for 'ADDRESS_STATE'
        $result = $this->searchAddressBook->searchPersonalAddressList('T', self::PERSONAL_ADDRESS_STATE, $partiesList);
        $this->assertNotNull($result);

        // Test when searching for 'ADDRESS_ZIP'
        $result = $this->searchAddressBook->searchPersonalAddressList('7', self::PERSONAL_ADDRESS_ZIP, $partiesList);
        $this->assertNotNull($result);
    }
}
