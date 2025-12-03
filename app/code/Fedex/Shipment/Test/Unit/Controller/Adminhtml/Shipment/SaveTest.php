<?php

namespace Fedex\Shipment\Test\Unit\Controller\Adminhtml\shipment;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Phrase;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Message\ManagerInterface;
use Magento\Backend\Model\Session;
use Fedex\Shipment\Model\Shipment;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Fedex\Shipment\Controller\Adminhtml\shipment\Save;

/**
 * Test class for Fedex\Shipment\Controller\Adminhtml\shipment\Save
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class SaveTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectManager|MockObject */
    protected $objectManagerHelper;

    /** @var PageFactory|MockObject */
    protected $pageFactory;

    /** @var Registry|MockObject */
    protected $registry;

    /** @var Http|MockObject */
    protected $requestMock;

    /** @var ManagerInterface|MockObject */
    protected $messageManager;

    /** @var Session|MockObject */
    protected $session;

    /** @var Shipment|MockObject */
    protected $shipment;

    /** @var RedirectFactory|MockObject */
    protected $resultRedirectFactory;

    /** @var Save|MockObject */
    protected $save;

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
        $this->requestMock = $this->createMock(Http::class);
        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
                    ->disableOriginalConstructor()
                    ->getMock();
        $this->session = $this->getMockBuilder(Session::class)
        ->setMethods(['setFormData','_getSession'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->shipment = $this->getMockBuilder(Shipment::class)
        ->setMethods(['load', 'setCreatedAt','setData','save'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory = $this->getMockBuilder(RedirectFactory::class)
            ->setMethods(['create', 'setPath'])
                    ->disableOriginalConstructor()
                    ->getMock();
        $this->pageFactory = $this->getMockBuilder(PageFactory::class)
        ->setMethods(['create','setActiveMenu','addBreadcrumb','getConfig','getTitle','prepend'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->save = $this->objectManagerHelper->getObject(
            Save::class,
            [   'request' => $this->requestMock,
                'messageManager' => $this->messageManager,
                'shipment' => $this->shipment,
                'session' => $this->session,
                '_request' => $this->requestMock,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'resultPageFactory' => $this->pageFactory
            ]
        );
    }

    /**
     * Test testExecute method.
     */
    public function testExecute()
    {
        $this->requestMock->expects($this->any())->method('getPostValue')->willReturn("test");
        $request = ['id'=>'1','back'=>'2'];
        $this->requestMock->expects($this->any())->method('getParam')->willReturn([$request]);
        $this->shipment->expects($this->any())->method('load')->willReturnSelf();
        $this->shipment->expects($this->any())->method('setCreatedAt')->willReturn("20-09-2021");
        $this->shipment->expects($this->any())->method('setData')->willReturn("test");
        $this->shipment->expects($this->any())->method('save')->willReturnSelf();
        $this->messageManager->expects($this->any())->method('addSuccess')->willReturn("The Shipment has been saved.");
        $this->session->expects($this->any())->method('setFormData')->willReturn("false");
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())->method('setPath')->willReturnSelf("*/*/edit");
        $this->assertEquals($this->resultRedirectFactory, $this->save->execute());
    }

    /**
     * Test testExecuteWithoutBack method.
     */
    public function testExecuteWithoutBack()
    {
        $this->requestMock->expects($this->any())->method('getPostValue')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())->method('setPath')->willReturnSelf("*/*/");
        $this->assertEquals($this->resultRedirectFactory, $this->save->execute());
    }

    /**
     * Test testExecuteWithoutBack method.
     */
    public function testExecuteWithoutData()
    {
        $this->requestMock->expects($this->any())->method('getPostValue')->willReturn('');
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())->method('setPath')->willReturnSelf("*/*/");
        $this->assertEquals($this->resultRedirectFactory, $this->save->execute());
    }

    /**
     * Test testExecuteWithLocalizedException method.
     */
    public function testExecuteWithLocalizedException()
    {
        $this->requestMock->expects($this->any())->method('getPostValue')->willReturn("test");
        $this->requestMock->expects($this->any())->method('getParam')->with("id")->willReturn("2");
        $this->shipment->expects($this->any())->method('load')->willReturnSelf();
        $this->shipment->expects($this->any())->method('setCreatedAt')->willReturn("20-09-2021");
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->shipment->expects($this->any())->method('setData')->willReturn("test");
        $this->shipment->expects($this->any())->method('save')->willThrowException($exception);
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())->method('setPath')->willReturnSelf("*/*/edit");
        $this->assertEquals($this->resultRedirectFactory, $this->save->execute());
    }

    /**
     * Test testExecute method.
     */
    public function testExecuteWithException()
    {
        $this->requestMock->expects($this->any())->method('getPostValue')->willReturn("test");
        $this->requestMock->expects($this->any())->method('getParam')->with('id')->willReturn(1);
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())->method('setPath')->willReturnSelf("*/*/edit");
        $this->assertEquals($this->resultRedirectFactory, $this->save->execute());
    }

}
