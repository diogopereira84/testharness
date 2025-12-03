<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Controller\Adminhtml\Index;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use Fedex\CIDPSG\Model\Customer;
use Magento\Backend\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Fedex\CIDPSG\Controller\Adminhtml\Index\Edit;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Edit
 */
class EditTest extends TestCase
{
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /** @var ObjectManager|MockObject */
    protected $objectManagerHelper;

    /** @var Registry|MockObject */
    protected $registry;

    /** @var customer|MockObject */
    protected $customer;

    /** @var Session|MockObject */
    protected $session;

    /** @var RequestInterface|MockObject */
    protected $requestMock;

    /** @var ManagerInterface|MockObject */
    protected $messageManager;

    /** @var RedirectFactory|MockObject */
    protected $resultRedirectFactory;

    /** @var PageFactory|MockObject */
    protected $pageFactory;

    /** @var Edit|MockObject */
    protected $edit;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->session = $this->getMockBuilder(Session::class)
            ->setMethods(['getFormData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->createMock(RequestInterface::class);
        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultRedirectFactory = $this->getMockBuilder(RedirectFactory::class)
            ->setMethods(['create', 'setPath'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->pageFactory = $this->getMockBuilder(PageFactory::class)
            ->setMethods(['create', 'setActiveMenu', 'addBreadcrumb', 'getConfig', 'getTitle', 'prepend'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->edit = $this->objectManagerHelper->getObject(
            Edit::class,
            [
                'logger' => $this->loggerMock,
                'resultPageFactory' => $this->pageFactory,
                'registry' => $this->registry,
                'customer' => $this->customer,
                'session' => $this->session,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'requestInterface' => $this->requestMock,
                'messageManager' => $this->messageManager
            ]
        );
    }

    /**
     * Test testExecute method
     *
     * @return void
     */
    public function testExecuteWithCustomerId()
    {
        $this->requestMock->expects($this->any())->method('getParam')->with("entity_id")->willReturn("2");
        $this->messageManager->expects($this->any())->method('addError')->willReturn("This item no longer exists.");
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())->method('setPath')->willReturnSelf("*/*/");
        $this->assertEquals($this->resultRedirectFactory, $this->edit->execute());
    }

    /**
     * Test testExecute method.
     *
     * @return void
     */
    public function testExecuteWithoutCustomerId()
    {
        $testMethod = new \ReflectionMethod(
            \Fedex\CIDPSG\Controller\Adminhtml\Index\Edit::class,
            '_isAllowed',
        );
        $testMethod->invoke($this->edit);
        $this->requestMock->expects($this->any())->method('getParam')->with("entity_id")->willReturn("");
        $this->session->expects($this->any())->method('getFormData')->willReturn([]);
        $this->pageFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->pageFactory->expects($this->any())->method('setActiveMenu')->willReturnSelf();
        $this->pageFactory->expects($this->any())->method('addBreadcrumb')->willReturnSelf();
        $this->pageFactory->expects($this->any())->method('getConfig')->willReturnSelf();
        $this->pageFactory->expects($this->any())->method('getTitle')->willReturnSelf();
        $this->pageFactory->expects($this->any())->method('prepend')->willReturn("Manage Shipment Status");
        $this->assertEquals($this->pageFactory, $this->edit->execute());
    }

    /**
     * Test testExecute method
     *
     * @return void
     */
    public function testExecuteWithCustomerData()
    {
        $this->requestMock->expects($this->any())->method('getParam')->with("entity_id")->willReturn("");
        $this->session->expects($this->any())->method('getFormData')->willReturn(["test"]);
        $this->pageFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->pageFactory->expects($this->any())->method('setActiveMenu')->willReturnSelf();
        $this->pageFactory->expects($this->any())->method('addBreadcrumb')->willReturnSelf();
        $this->pageFactory->expects($this->any())->method('getConfig')->willReturnSelf();
        $this->pageFactory->expects($this->any())->method('getTitle')->willReturnSelf();
        $this->pageFactory->expects($this->any())->method('prepend')->willReturn("Manage Shipment Status");
        $this->assertEquals($this->pageFactory, $this->edit->execute());
    }
}
