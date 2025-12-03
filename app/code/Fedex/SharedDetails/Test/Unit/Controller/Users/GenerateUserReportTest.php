<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SharedDetails\Test\Unit\Controller\Users;

use Fedex\SharedDetails\Controller\Users\GenerateUserReport;
use Fedex\SharedDetails\Helper\CommercialReportHelper;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Convert\ExcelFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\Write;
use Magento\Framework\Filesystem\File\WriteInterface as FileWriteInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use \Magento\Framework\App\RequestInterface;
use Magento\Company\Model\ResourceModel\Users\Grid\CollectionFactory;

/**
 * Test class for Fedex\SharedDetails\Controller\Users\GenerateUserReportTest
 */
class GenerateUserReportTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    protected $contextMock;
    /**
     * @var (\Magento\Framework\App\Action\Action & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $ActionMock;
    /**
     * @var (\Magento\Framework\App\Action\HttpGetActionInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $httpMock;
    protected $requestMock;
    protected $sessionFactoryMock;
    protected $customerSessionMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var (\Magento\Framework\App\RequestInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $request;
    /**
     * @var (\Fedex\SharedDetails\Test\Unit\Controller\Users\Session & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $session;
    /**
     * @var (\Magento\Framework\App\RequestInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $requestInterface;
    protected $stream;
    protected $pageFactoryMock;
    /**
     * @var (\Magento\Framework\Convert\ExcelFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $excelFactory;
    /**
     * @var (\Magento\Framework\View\Result\Page & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $pageMock;
    protected $filesystem;
    protected $directoryWriteMock;
    /**
     * @var (\Magento\Framework\App\Filesystem\DirectoryList & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $directoryList;
    protected $commercialReportHelperMock;
    protected $customer;
    /**
     * @var GenerateUserReport
     */
    private $generateReportController;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var MockObject
     */
    protected $selectMock;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private CompanyRepositoryInterface $companyRepositoryMock;

    /**
     * @var CollectionFactory|MockObject
     */
    private $userCollectionFactory;

    /** @var int */
    private $companyId;

    protected function setUp(): void
    {
        $this->companyId = 48;

        $this->objectManager = new ObjectManager($this);

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->contextMock = $this->createMock(Context::class);
        $this->ActionMock = $this->createMock(\Magento\Framework\App\Action\Action::class);
        $this->httpMock = $this->createMock(HttpGetActionInterface::class);
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->companyRepositoryMock = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $this->userCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();

        $this->sessionFactoryMock = $this->getMockBuilder(SessionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'getCustomerCompany', 'getSecondaryEmail', 'getCustomer', 'getEmail'])
            ->getMock();

        $this->customerSessionMock = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCustomerCompany',
                'getCustomer',

            ])
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['critical', 'error'])
            ->getMockForAbstractClass();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomer', 'getSecondaryEmail', 'getEmail'])
            ->getMock();

        $this->requestInterface = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->stream = $this->getMockBuilder(FileWriteInterface::class)
            ->onlyMethods(['lock', 'unlock', 'close'])
            ->getMockForAbstractClass();

        $this->pageFactoryMock = $this->getMockBuilder(PageFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->excelFactory = $this->getMockBuilder(ExcelFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->pageMock = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->filesystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDirectoryWrite'])
            ->getMock();

        $this->directoryWriteMock = $this->getMockBuilder(Write::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'isDirectory', 'lock', 'openFile', 'writeCsv'])
            ->getMock();

        $this->filesystem->expects($this->any())->method('getDirectoryWrite')->willReturn($this->directoryWriteMock);

        $this->directoryList = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPath', 'create'])
            ->getMock();

        $this->commercialReportHelperMock = $this->getMockBuilder(CommercialReportHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(
                ['getAttributeSet', 'sendEmail', 'getBranchId', 'sendUserReportEmail']
            )
            ->getMock();

        $this->userCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'count', 'addFieldToSelect',
                'addAttributeToFilter', 'getSelect', 'join', 'joinLeft', 'where', 'getTable', 'getAllItems', 'setOrder', 'create','toArray'])
            ->getMock();

        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGroupId', 'getSecondaryEmail', 'getEmail'])
            ->getMock();

        $this->generateReportController = $this->objectManager->getObject(
            GenerateUserReport::class,
            [
                'context' => $this->contextMock,
                'companyRepository' => $this->companyRepositoryMock,
                'userCollectionFactory' => $this->userCollectionFactory,
                'customerSession' => $this->sessionFactoryMock,
                'logger' => $this->loggerMock,
                'resultPageFactory' => $this->pageFactoryMock,
                'filesystem' => $this->filesystem,
                'directory' => $this->directoryWriteMock,
                'commercialReportHelper' => $this->commercialReportHelperMock,

            ]
        );
    }

    /**
     * Test method to Get a row data of the particular columns
     *
     * @return string[]
     */
    public function testGetUserRowRecord()
    {
        $orderItem = [
            'order_id' => 123,
            'product_id' => 123,
            'product_options' => 'catalog',
            'name' => 'Sample Product Name',
            'source' => 'Send & Print',
            'qty_ordered' => '10',
            'price' => '$10',
            'row_total' => '10',
            'customer_status' => 1,
            'created_at' => 'Apr 25, 2024'
        ];
        $this->sessionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->customerSessionMock);

        $this->customerSessionMock->expects($this->any())->method('getCustomerCompany')->willReturn(48);
        $company = $this->getMockForAbstractClass(
            CompanyInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getCompanyName'],
        );
        $company->expects($this->any())->method('getCompanyName')->willReturn('test');
        $this->companyRepositoryMock->expects($this->any())
            ->method('get')->willReturn($company);
        $this->commercialReportHelperMock->expects($this->any())->method
        ('getBranchId')->with($orderItem['order_id'])->willReturn('test');
        $this->customerSessionMock->expects($this->any())
            ->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())
            ->method('getGroupId')->willReturn(89);
        $this->assertNotNull($this->generateReportController->getUserRowRecord($orderItem));
    }

    /**
     * Test method to Generate Report Execute action.
     *
     * @return PageFactory
     */
    public function testexecute()
    {
        $items['items'][0] = [
            'order_id' => 123,
            'product_id' => 123,
            'product_options' => 'catalog',
            'name' => 'Sample Product Name',
            'source' => 'Send & Print',
            'qty_ordered' => '10',
            'price' => '$10',
            'row_total' => '10',
            'customer_status' => 1,
            'created_at' => 'Apr 25, 2024'
        ];
        $this->pageFactoryMock->expects($this->any())->method('create')
            ->willReturnSelf();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->withConsecutive(
                ['emailData'],
                ['userIds'],
                ['userIds']
            )->willReturnOnConsecutiveCalls('123', '1,2', '1,2');
        $this->sessionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->customerSessionMock);
        $this->customerSessionMock->expects($this->any())->method('getCustomerCompany')->willReturn(48);
        $this->customerSessionMock->expects($this->any())
            ->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())
            ->method('getGroupId')->willReturn(89);
        $this->customer->expects($this->any())->method('getSecondaryEmail')->willReturn("tst@gmail.com");
        $this->customer->expects($this->any())->method('getEmail')->willReturn("test@gmail.com");
        $this->userCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturnSelf();
        $this->userCollectionFactory->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->userCollectionFactory->expects($this->any())->method('getSelect')->willReturnSelf();
        $this->userCollectionFactory->expects($this->any())->method('join')->willReturnSelf();
        $this->userCollectionFactory->expects($this->any())->method('where')->willReturnSelf();
        
        $this->userCollectionFactory->expects($this->any())->method('setOrder')->willReturnSelf();
        $this->userCollectionFactory->expects($this->any())->method('toArray')->willReturn($items);
        $this->directoryWriteMock->expects($this->any())->method('create')->willReturnSelf();
        $this->directoryWriteMock->expects($this->any())->method('openFile')->willReturn($this->stream);
        $result = $this->generateReportController->execute();
        $this->assertNotNull($result);
    }
    
    /**
     * Test method to Generate Report Execute action.
     *
     * @return PageFactory
     */
    public function testElseExecute()
    {
        $items['items'][0] = [
            'order_id' => 123,
            'product_id' => 123,
            'product_options' => 'catalog',
            'name' => 'Sample Product Name',
            'source' => 'Send & Print',
            'qty_ordered' => '10',
            'price' => '$10',
            'row_total' => '10',
            'customer_status' => 1,
            'created_at' => 'Apr 25, 2024'
        ];
        $this->pageFactoryMock->expects($this->any())->method('create')
            ->willReturnSelf();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->withConsecutive(
                ['emailData'],
                ['userIds'],
                ['userIds']
            )->willReturnOnConsecutiveCalls(null, '1,2','1,2');
        $this->sessionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->customerSessionMock);
        $this->customerSessionMock->expects($this->any())->method('getCustomerCompany')->willReturn(48);
        $this->customerSessionMock->expects($this->any())
            ->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())
            ->method('getGroupId')->willReturn(89);
        $this->customer->expects($this->any())->method('getSecondaryEmail')->willReturn("tst@gmail.com");
        $this->customer->expects($this->any())->method('getEmail')->willReturn("test@gmail.com");
        $this->userCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturnSelf();
        $this->userCollectionFactory->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->userCollectionFactory->expects($this->any())->method('getSelect')->willReturnSelf();
        $this->userCollectionFactory->expects($this->any())->method('join')->willReturnSelf();
        $this->userCollectionFactory->expects($this->any())->method('where')->willReturnSelf();
        $this->userCollectionFactory->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->userCollectionFactory->expects($this->any())->method('setOrder')->willReturnSelf();
        $this->userCollectionFactory->expects($this->any())->method('toArray')->willReturn($items);
        $this->directoryWriteMock->expects($this->any())->method('openFile')->willReturn($this->stream);
        $result = $this->generateReportController->execute();
        $this->assertNotNull($result);
    }

    /**
     * Test method to Generate Report Execute action.
     *
     * @return PageFactory
     */
    public function testexecuteWithException()
    {
        $items['items'][0] = [
            'order_id' => 123,
            'product_id' => 123,
            'product_options' => 'catalog',
            'name' => 'Sample Product Name',
            'source' => 'Send & Print',
            'qty_ordered' => '10',
            'price' => '$10',
            'row_total' => '10',
            'customer_status' => 1,
            'created_at' => 'Apr 25, 2024'
        ];
        $this->pageFactoryMock->expects($this->any())->method('create')
            ->willReturnSelf();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->withConsecutive(
                ['emailData'],
                ['userIds'],
                ['userIds']
            )->willReturnOnConsecutiveCalls(null, '1,2','1,2');
        $this->sessionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->customerSessionMock);
        $this->customerSessionMock->expects($this->any())->method('getCustomerCompany')->willReturn(48);
        $this->customerSessionMock->expects($this->any())
            ->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())
            ->method('getGroupId')->willReturn(89);
        $this->customer->expects($this->any())->method('getSecondaryEmail')->willReturn("tst@gmail.com");
        $this->customer->expects($this->any())->method('getEmail')->willReturn("test@gmail.com");
        $this->userCollectionFactory->expects($this->any())
            ->method('create')
            ->willThrowException(
                new \Exception("The product that was requested doesn't exist. Verify the product and try again.")
            );
        $result = $this->generateReportController->execute();
        $this->assertNotNull($result);
    }

}
