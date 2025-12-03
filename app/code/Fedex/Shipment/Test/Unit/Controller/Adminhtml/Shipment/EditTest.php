<?php

namespace Fedex\Shipment\Test\Unit\Controller\Adminhtml\shipment;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use Fedex\Shipment\Model\Shipment;
use Magento\Backend\Model\Session;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Message\ManagerInterface;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Fedex\Shipment\Controller\Adminhtml\shipment\Edit;

/**
 * Test class for Fedex\Shipment\Controller\Adminhtml\shipment\Edit
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class EditTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectManager|MockObject */
    protected $objectManagerHelper;

    /** @var Registry|MockObject */
    protected $registry;

    /** @var Shipment|MockObject */
    protected $shipment;

    /** @var Session|MockObject */
    protected $session;

    /** @var Http|MockObject */
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
        $this->shipment = $this->getMockBuilder(Shipment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->session = $this->getMockBuilder(Session::class)
            ->setMethods(['getFormData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->createMock(Http::class);
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
        $this->edit = $this->objectManagerHelper->getObject(
            Edit::class,
            [
                'request' => $this->requestMock,
                'registry' => $this->registry,
                'messageManager' => $this->messageManager,
                'shipment' => $this->shipment,
                'session' => $this->session,
                '_request' => $this->requestMock,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'resultPageFactory' => $this->pageFactory,
            ]
        );
    }

    /**
     * Test testExecuteWithShipmentId method.
     */
    public function testExecuteWithShipmentId()
    {
        $this->requestMock->expects($this->any())->method('getParam')->with("id")->willReturn("2");
        $this->messageManager->expects($this->any())->method('addError')->willReturn("This item no longer exists.");
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturnSelf();;
        $this->pageFactory->expects($this->any())->method('setActiveMenu')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())->method('setPath')->willReturnSelf("*/*/");
        // $this->invokeMethod()
        $this->assertEquals($this->resultRedirectFactory, $this->edit->execute());
    }

    /**
     * Test testExecuteWithoutShipmentId method.
     */
    public function testExecuteWithoutShipmentId()
    {
        $testMethod = new \ReflectionMethod(
            \Fedex\Shipment\Controller\Adminhtml\shipment\Edit::class,
            '_isAllowed',
        );
        $testMethod->invoke($this->edit);
        $this->requestMock->expects($this->any())->method('getParam')->with("id")->willReturn("");
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
     * Test testExecuteWithShipmentData method.
     */
    public function testExecuteWithShipmentData()
    {
        $this->requestMock->expects($this->any())->method('getParam')->with("id")->willReturn("");
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
