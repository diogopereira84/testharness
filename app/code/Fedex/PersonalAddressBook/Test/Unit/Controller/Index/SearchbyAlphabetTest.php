<?php
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\PersonalAddressBook\Controller\Index\SearchbyAlphabet;
use Magento\Framework\App\Action\Context;
use Fedex\PersonalAddressBook\Helper\Parties;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;

class SearchbyAlphabetTest extends TestCase
{
    private $searchByAlphabet;
    private $contextMock;
    private $partiesHelperMock;
    private $customerSessionMock;
    private $jsonFactoryMock;
    private $loggerMock;
    private $requestMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->partiesHelperMock = $this->createMock(Parties::class);
        $this->customerSessionMock = $this->getMockBuilder(Session::class)
                                     ->setMethods(['getPartiesList','setPartiesList','getAddressBookPageSize'])
                                     ->disableOriginalConstructor()
                                     ->getMockForAbstractClass();
        $this->jsonFactoryMock = $this->createMock(JsonFactory::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPost'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        // Creating an instance of the SearchbyAlphabet controller
        $this->searchByAlphabet = new SearchbyAlphabet(
            $this->contextMock,
            $this->partiesHelperMock,
            $this->customerSessionMock,
            $this->jsonFactoryMock,
            $this->loggerMock,
            $this->requestMock
        );
    }

    public function testExecuteWithAlphabet()
    {
        // Mock request with alphabet and clear filter parameters
        $this->requestMock->expects($this->any())
            ->method('getPost')
            ->will($this->returnValueMap([
                ['alphabet', 'A'],
                ['clear', false]
            ]));

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
        $this->searchByAlphabet->execute();
    }

    public function testExecuteWithClearFilter()
    {
        // Mock request with clear filter
        $this->requestMock->expects($this->any())
            ->method('getPost')
            ->will($this->returnValueMap([
                ['alphabet', 'A'],
                ['clear', true]
            ]));

        // Mock parties list
        $partiesList = [
          (object)  ['firstName' => 'Alice'],
          (object) ['firstName' => 'Bob']
        ];

        $this->customerSessionMock->method('getPartiesList')
            ->willReturn(json_encode($partiesList));

        $this->partiesHelperMock->method('paginatedData')->willReturn($partiesList);

        // Mock result Json
        $resultJsonMock = $this->createMock(\Magento\Framework\Controller\Result\Json::class);
        $this->jsonFactoryMock->method('create')
            ->willReturn($resultJsonMock);

        // Call the execute method
        $this->searchByAlphabet->execute();
    }

    public function testExecuteWithException()
    {
        // Mock request to throw an exception
        $this->requestMock->expects($this->any())
            ->method('getPost')
            ->will($this->returnValueMap([
                ['alphabet', 'A'],
                ['clear', false]
            ]));

        $this->customerSessionMock->method('getPartiesList')
            ->willThrowException(new \Exception("Database error"));

        // Mock result Json
        $resultJsonMock = $this->createMock(\Magento\Framework\Controller\Result\Json::class);
        $this->jsonFactoryMock->method('create')
            ->willReturn($resultJsonMock);

        // Call the execute method
        $this->searchByAlphabet->execute();
    }

    public function testSearchAddress()
    {
        $partiesList = [
         (object) ['firstName' => 'Alice'],
         (object) ['firstName' => 'Bob'],
         (object) ['firstName' => 'Anna']
        ];

        // Test when searching for 'A'
        $result = $this->searchByAlphabet->searchAddress('A', 'firstName', $partiesList);
        $this->assertCount(2, $result);
        $this->assertEquals('Alice', $result[0]->firstName);
        $this->assertEquals('Anna', $result[1]->firstName);

        // Test when searching for 'B'
        $result = $this->searchByAlphabet->searchAddress('B', 'firstName', $partiesList);
        $this->assertCount(1, $result);
        $this->assertEquals('Bob', $result[0]->firstName);
    }
}
