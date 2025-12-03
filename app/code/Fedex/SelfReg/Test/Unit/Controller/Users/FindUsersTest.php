<?php

namespace Fedex\SelfReg\Test\Unit\Controller\Users;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\SelfReg\Controller\Users\FindUsers;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Company\Model\Company\Structure;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModek\Customer\Collection as CustomerCollection;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Context;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Model\Company;

class FindUsersTest extends TestCase
{
    
    protected $companyRepositoryInterface;
    protected $companyModel;
    protected $context;
    protected $jsonFactory;
    protected $resultFactory;
    protected $structure;
    protected $session;
    protected $customer;
    protected $customerCollection;
    protected $resourceConnection;
    protected $requestInterface;
    protected $findUsersMock;
    protected $result;
    protected $jsonMock;
    protected $CompanyRepositoryInterface;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();
        $this->companyRepositoryInterface = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get','save','delete','deleteById','getList'])
            ->getMock();
        $this->companyModel = $this->getMockBuilder(Company::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSuperUserId'])
            ->getMock();
        $this->structure = $this->getMockBuilder(Structure::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOndemandCompanyInfo'])
            ->getMock();
        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMEthods(['getCollection','getName','getId'])
            ->getMock();
        $this->customerCollection = $this->getMockBuilder(CustomerCollection::class)
            ->disableOriginalConstructor()
            ->setMEthods(['getCollection','addAttributeToSelect','addFieldToFilter','addAttributeToFilter','getSelect','join','getIterator'])
            ->getMock();
        $this->resourceConnection = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->setMEthods(['getConnection','getTableName'])
            ->getMock();
        $this->requestInterface = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPost'])
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->findUsersMock = $objectManager->getObject(
            FindUsers::class,
            [
                'structure' => $this->structure,
                'session' => $this->session,
                'resultFactory' => $this->resultFactory,
                'resultJsonFactory' => $this->jsonFactory,
                'customer' => $this->customer,
                'resource' => $this->resourceConnection,
                'companyRepositoryInterface' => $this->companyRepositoryInterface,
                'request' => $this->requestInterface
            ]
        );
    }

    /** 
     * Test Case for Exectue Method With Post Data
    */
    public function testExecute()
    {
        $postData = ['filter' => 'Test','exclude_user' => '23,56'];
        $this->session->expects($this->any())->method('getOndemandCompanyInfo')->willReturn(['company_id' => '98']);
        $this->requestInterface->expects($this->any())->method('getPost')->willReturn($postData);
        $this->companyRepositoryInterface->expects($this->any())->method('get')->willReturn($this->companyModel);
        $this->companyModel->expects($this->any())->method('getSuperUserId')->willReturn(1);
        $this->resourceConnection->expects($this->any())->method('getConnection')->willReturnSelf();
        $this->resourceConnection->expects($this->any())->method('getTableName')->willReturn('company_advanced_customer_entity');
        $this->customer->expects($this->any())->method('getCollection')->willReturn($this->customerCollection);
        $this->customerCollection->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->customerCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->customerCollection->expects($this->any())->method('addAttributeToFilter')->willReturnSelf();
        $this->customerCollection->expects($this->any())->method('getSelect')->willReturnSelf();
        $this->customerCollection->expects($this->any())->method('join')->willReturnSelf();
        $this->customerCollection->expects($this->any())->method('getIterator')->willReturn(new \ArrayIterator([$this->customer]));
        $this->customer->expects($this->any())->method('getName')->willReturn('Test User');
        $this->customer->expects($this->any())->method('getId')->willReturn('25');
        $this->jsonFactory->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->findUsersMock->execute();
    }

    /** 
     * Test Case for Exectue Method Without Post Data
    */
    public function testExecuteWithoutPostData()
    {
        $postData = [];
        $this->session->expects($this->any())->method('getOndemandCompanyInfo')->willReturn(['company_id' => '98']);
        $this->requestInterface->expects($this->any())->method('getPost')->willReturn($postData);
        $this->jsonFactory->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->findUsersMock->execute();
    }
}
