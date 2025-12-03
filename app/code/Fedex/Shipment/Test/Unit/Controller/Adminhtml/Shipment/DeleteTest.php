<?php

namespace Fedex\Shipment\Test\Unit\Controller\Adminhtml\shipment;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Message\ManagerInterface;
use Fedex\Shipment\Model\Shipment;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Fedex\Shipment\Controller\Adminhtml\shipment\Delete;

/**
 * Test class for Fedex\Shipment\Controller\Adminhtml\shipment\Delete
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class DeleteTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectManagerHelper|MockObject */
    protected $objectManagerHelper;

    /** @var Registry|MockObject */
    protected $registry;

    /** @var Http|MockObject */
    protected $requestMock;

    /** @var ManagerInterface|MockObject */
    protected $messageManager;

    /** @var Shipment|MockObject */
    protected $shipment;

    /** @var RedirectFactory|MockObject */
    protected $resultRedirectFactory;

    /** @var PageFactory|MockObject */
    protected $pageFactory;

    /** @var Delete|MockObject */
    protected $delete;

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

        $this->shipment = $this->getMockBuilder(Shipment::class)
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

        $this->delete = $this->objectManagerHelper->getObject(
            Delete::class,
            [
                'messageManager' => $this->messageManager,
                'shipment' => $this->shipment,
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
        $this->requestMock->expects($this->any())->method('getParam')->with("id")->willReturn("2");
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->shipment->expects($this->any())->method('load')->willReturnSelf();
        $this->shipment->expects($this->any())->method('delete')->willReturnSelf();
        $this->messageManager->expects($this->any())->method('addSuccess')->willReturn("This item no longer exists.");
        $this->resultRedirectFactory->expects($this->any())->method('setPath')->willReturnSelf("*/*/");
        $this->assertEquals($this->resultRedirectFactory, $this->delete->execute());
    }

    /**
     * Test testExecuteWithoutId method.
     */
    public function testExecuteWithoutId()
    {
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->messageManager->expects($this->any())->method('addError')->willReturn("This item no longer exists.");
        $this->resultRedirectFactory->expects($this->any())->method('setPath')->willReturnSelf("*/*/");
        $this->assertEquals($this->resultRedirectFactory, $this->delete->execute());
    }

    /**
     * Test testExecuteWithException method.
     */
    public function testExecuteWithException()
    {
        $this->requestMock->expects($this->any())->method('getParam')->with("id")->willReturn("2");
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturnSelf();
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->shipment->expects($this->any())->method('load')->willThrowException($exception);
        $this->messageManager->expects($this->any())->method('addError')->willReturn("This item no longer exists.");
        $this->resultRedirectFactory->expects($this->any())->method('setPath')->willReturnSelf("*/*/");
        $this->assertEquals($this->resultRedirectFactory, $this->delete->execute());
    }
}
