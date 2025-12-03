<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CustomerGroup\Test\Unit\Controller\Adminhtml\Options;

use Magento\Backend\App\Action\Context;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Customer\Model\Model\Customer as CustomerData;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\App\ResponseInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\Phrase;
use Exception;
use Fedex\CustomerGroup\Controller\Adminhtml\Options\Save;
use Magento\Company\Api\CompanyManagementInterface;

class SaveTest extends TestCase
{
    protected $logger;
    protected $customerInterface;
    protected $responseInterface;
    protected $companyRepository;
    protected $companyInterface;
    protected $updateCustomerAttributeMethod;
    /**
     * @var Save
     */
    protected $save;
    /**
     * @var ResponseInterface
     */
    protected $response;
    /**
     * @var ManagerInterface
     */
    protected $messageManager;
    /**
     * @var ObjectManager
     */
    protected $objectManagerHelper;
    /**
     * @var Http
     */
    protected $requestMock;
    /**
     * @var Context
     */
    protected $context;
    /**
     * @var CustomerRepository
     */
    protected $customerRepository;
    /**
     * @var CustomerFactory
     */
    protected $customerFactory;
    /**
     * @var Customer
     */
    protected $customer;
    /**
     * @var CustomerData
     */
    protected $customerData;
    /**
     * @var Data
     */
    protected $jsonHelper;
    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->context = $this->createMock(Context::class);

        $this->requestMock = $this->createMock(Http::class);

        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->setMethods(['addSuccessMessage'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerFactory = $this->getMockBuilder(CustomerFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customer = $this->getMockBuilder(Customer::class)
            ->setMethods(['save'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['info','critical'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->jsonHelper = $this->getMockBuilder(Data::class)
            ->setMethods(['jsonEncode'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerRepository = $this->getMockBuilder(CustomerRepository::class)
            ->setMethods(['getById'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerInterface = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerData = $this->getMockBuilder(CustomerData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->responseInterface = $this->getMockForAbstractClass(
            ResponseInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['representJson']
        );
        $this->companyRepository = $this->getMockBuilder(CompanyManagementInterface::class)
            ->setMethods(['getByCustomerId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyInterface = $this->getMockBuilder(\Magento\Company\Api\Data\CompanyInterface::class)
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        $this->save = $this->objectManagerHelper->getObject(
            Save::class,
            [
                'context' => $this->context,
                'messageManager' => $this->messageManager,
                'customer' => $this->customer,
                'customerFactory' => $this->customerFactory,
                'customerRepository' => $this->customerRepository,
                '_request' => $this->requestMock,
                '_response' => $this->responseInterface,
                'resultFactory' => $this->resultFactory,
                'jsonHelper' => $this->jsonHelper,
                'logger' => $this->logger,
                'companyRepository' => $this->companyRepository
            ]
        );
        $this->updateCustomerAttributeMethod = new \ReflectionMethod(
            Save::class,
            'updateCustomerAttribute'
        );
        $this->updateCustomerAttributeMethod->setAccessible(true);
    }
    public function testExecuteSuccess()
    {
        $this->prepareRequestMock();
        $this->companyRepository->expects($this->any())
        ->method('getByCustomerId')
        ->willReturn($this->companyInterface);
        $customerMock = $this->getMockBuilder(\Magento\Customer\Model\Customer::class)
        ->setMethods(['setWebsiteId','loadByEmail','setGroupId'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->customerRepository
        ->expects($this->any())
        ->method('getById')
        ->willReturn($this->customerInterface);
        $this->customerFactory
        ->expects($this->any())
        ->method('create')
        ->willReturn($this->customerData);
        $customerMock->expects($this->any())
        ->method('setWebsiteId')
        ->willReturnSelf();
        $customerMock->expects($this->any())
        ->method('loadByEmail')
        ->willReturnSelf();
        $customerMock->expects($this->any())
        ->method('setGroupId')
        ->with(2)
        ->willReturnSelf();
        $this->messageManager->expects($this->any())->method('addSuccessMessage')->willReturn(("Saved"));
        $this->jsonHelper->expects($this->any())->method('jsonEncode')->willReturnSelf();
        $this->responseInterface->expects($this->any())->method('representJson')->willReturnSelf();
        $this->customerRepository->expects($this->never())
        ->method('save');
        $result = $this->save->execute();
        $this->assertNotNull($result);
    }
    /**
     * Test testExecute method.
     *
     * @return void
     */
    public function testUpdateCustomerAttribute()
    {
        $customerId = 1;
        $customerGroupId = 2;

        $customerMock = $this->getMockBuilder(\Magento\Customer\Model\Customer::class)
            ->setMethods(['setWebsiteId','loadByEmail','setGroupId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerRepository->expects($this->any())
            ->method('getById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->customerFactory->expects($this->any())
            ->method('create')
            ->willReturn($customerMock);

        $customerMock->expects($this->any())
            ->method('setWebsiteId')
            ->willReturnSelf();

        $customerMock->expects($this->any())
            ->method('loadByEmail')
            ->willReturnSelf();

        $customerMock->expects($this->any())
            ->method('setGroupId')
            ->with($customerGroupId)
            ->willReturnSelf();

        $this->customerRepository->expects($this->never())
            ->method('save');
        $this->assertNull($this->save->updateCustomerAttribute($customerId, $customerGroupId));
    }
    public function testUpdateCustomerAttributeException()
    {
        $exception = new \Exception();
        $customerId = 1;
        $customerGroupId = 2;
        $customerMock = $this->getMockBuilder(\Magento\Customer\Model\Customer::class)
            ->setMethods(['setWebsiteId','loadByEmail','setGroupId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerRepository->expects($this->any())
            ->method('getById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->customerFactory->expects($this->any())
            ->method('create')
            ->willReturn($customerMock);

        $customerMock->expects($this->any())
            ->method('setWebsiteId')
            ->willReturnSelf();

        $customerMock->expects($this->any())
            ->method('loadByEmail')
            ->willReturnSelf();

        $customerMock->expects($this->any())
            ->method('setGroupId')
            ->with($customerGroupId)
            ->willReturnSelf();
        $this->customer->expects($this->any())
            ->method('save')
            ->willThrowException($exception);
        $this->assertNull($this->save->updateCustomerAttribute($customerId, $customerGroupId));
    }
    /**
     * Test for ExecuteWithException
     */
    public function testExecuteWithException()
    {
        $phrase = new Phrase(__('Something went wrong. Please try again later.'));
        $exception = new \Exception($phrase);
        $this->requestMock->expects($this->any())->method('getParam')->willThrowException($exception);

        $this->assertNull($this->save->execute());
    }
    /**
     * Prepare Request Mock.
     *
     * @return void
     */
    private function prepareRequestMock()
    {
        $data = [
            'group' => 2,
            'selectedIds' => 3,6,15
        ];
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturnMap($data);
    }
}
