<?php

namespace Fedex\SelfReg\Controller\Adminhtml;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\Json as ResultJson;
use Fedex\SelfReg\Controller\Adminhtml\Index\GetCustomer;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Customer\Model\ResourceModek\Customer\Collection as CustomerCollection;



class GetCustomerTest extends \PHPUnit\Framework\TestCase
{
    protected $resultJsonFactory;
    protected $customer;
    protected $resource;
    protected $request;
    protected $resultJson;
    protected $adapterInterface;
    protected $customerCollection;
    /**
     * @var LeftMenu
     */
    protected $controller;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PageFactory
     */
    protected $resultPageFactoryMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->resultJsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection','getFirstname','getId', 'getLastname'])
            ->getMock();
        $this->resource = $this->getMockBuilder(ResourceConnection::class)
            ->setMethods(['getConnection'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPost'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->resultJson = $this->getMockBuilder(ResultJson::class)
            ->setMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->adapterInterface = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTableName'])
            ->getMockForAbstractClass();
        
        $this->customerCollection = $this->getMockBuilder(CustomerCollection::class)
            ->disableOriginalConstructor()
            ->setMEthods(['getCollection','addAttributeToSelect','addFieldToFilter','addAttributeToFilter','getSelect','join','getIterator'])
            ->getMock();

        $this->controller = $objectManager->getObject(
            GetCustomer::class,
            [
                'resultJsonFactory' => $this->resultJsonFactory,
                'customer' => $this->customer,
                'resource' => $this->resource,
                'request' => $this->request
            ]
        );
    }

    /**
     * Test execute method
     */
    public function testExecute()
    {
        $postData = ['site_id' => 456];
        $this->request->expects($this->any())->method('getPost')->willReturn($postData);
        $this->resultJsonFactory->expects($this->any())->method('create')->willReturn($this->resultJson);

        $this->resource->expects($this->any())->method('getConnection')->willReturn($this->adapterInterface);

        $this->adapterInterface->expects($this->any())->method('getTableName')->willReturn('company_advanced_customer_entity');

        $this->customer->expects($this->any())->method('getCollection')->willReturn($this->customerCollection);

        $this->customerCollection->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();

        $this->customerCollection->expects($this->any())->method('getSelect')->willReturnSelf();

        $this->customerCollection->expects($this->any())->method('join')->willReturnSelf();

        $this->customerCollection->expects($this->any())->method('getIterator')->willReturn(new \ArrayIterator([$this->customer]));

        $this->customer->expects($this->any())->method('getFirstname')->willReturn('Test User');

        $this->customer->expects($this->any())->method('getLastname')->willReturn('Test User');

        $this->customer->expects($this->any())->method('getId')->willReturn('25');

        $this->resultJson->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertNotNull($this->controller->execute());
    }
}
