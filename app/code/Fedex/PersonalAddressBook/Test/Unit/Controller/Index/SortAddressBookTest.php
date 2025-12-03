<?php
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\PersonalAddressBook\Controller\Index\SortAddressBook;
use Magento\Framework\App\Action\Context;
use Fedex\PersonalAddressBook\Helper\Parties;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;

class SortAddressBookTest extends TestCase
{
    public const PERSONAL_ADDRESS_STREET = 'personal_address_address';
    public const PERSONAL_ADDRESS_CITY = 'personal_address_city';
    public const PERSONAL_ADDRESS_STATE = 'personal_address_state';
    public const PERSONAL_ADDRESS_ZIP = 'personal_address_zip';

    protected $partiesHelperMock;
    protected $customerSessionMock;
    protected $toggleConfig;
    protected $jsonFactoryMock;
    protected $loggerMock;
    protected $requestMock;
    protected $sortAddressBook;

    protected function setUp(): void
    {
        $this->partiesHelperMock = $this->getMockBuilder(Parties::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->setMethods(['getPartiesList','getAddressBookPageSize','setPartiesList'])
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

        // Creating an instance of the SortAddressBook controller
        $this->sortAddressBook = new SortAddressBook(
            $this->loggerMock,
            $this->requestMock,
            $this->toggleConfig,
            $this->jsonFactoryMock,
            $this->partiesHelperMock,
            $this->customerSessionMock
        );
    }

    public function testExecute()
    {
        $this->toggleConfig->method('getToggleConfigValue')
            ->willReturn(true);
        // Mock request with searchField filter parameters
        $this->requestMock->expects($this->any())
            ->method('getPostValue')
            ->willReturn([
                'addressSortOption' => 'firstName',
                'order' => 'asc'
            ]);

        // Mock parties list
        $partiesList = [
            ['firstName' => 'Alice'],
            ['firstName' => 'Bob'],
            ['firstName' => 'Anna']
        ];

        $this->customerSessionMock->method('getPartiesList')
            ->willReturn(json_encode($partiesList));

        $this->customerSessionMock->method('getAddressBookPageSize')
            ->willReturn(10);

        // Mock result Json
        $resultJsonMock = $this->createMock(\Magento\Framework\Controller\Result\Json::class);
        $this->jsonFactoryMock->method('create')
            ->willReturn($resultJsonMock);

        // Call the execute method
        $this->assertNull($this->sortAddressBook->execute());
    }

    public function testExecuteWithDescending()
    {
        $this->toggleConfig->method('getToggleConfigValue')
            ->willReturn(true);
        // Mock request with searchField filter parameters
        $this->requestMock->expects($this->any())
            ->method('getPostValue')
            ->willReturn([
                'addressSortOption' => 'firstName',
                'order' => 'desc'
            ]);

        // Mock parties list
        $partiesList = [
            ['firstName' => 'Alice',
            'address' => [
                'streetLines' => ['0' => 'ab'],
                'city' => 'Plano',
                'stateOrProvinceCode' => 'TX',
                'postalCode' => '75024'
            ]],
            ['firstName' => 'Bob'],
            ['firstName' => 'Anna']
        ];

        $this->customerSessionMock->method('getPartiesList')
            ->willReturn(json_encode($partiesList));

        // Mock result Json
        $resultJsonMock = $this->createMock(\Magento\Framework\Controller\Result\Json::class);
        $this->jsonFactoryMock->method('create')
            ->willReturn($resultJsonMock);

        // Call the execute method
        $this->assertNull($this->sortAddressBook->execute());
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
        $this->assertNull($this->sortAddressBook->execute());
    }
}
