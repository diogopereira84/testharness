<?php

namespace Fedex\Shipment\Test\Unit\Controller\Adminhtml\shipment;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Sales\Model\Order;
use Magento\Framework\Controller\Result\RedirectFactory;
use Fedex\Shipment\Controller\Adminhtml\shipment\Deleteorder;
use \Magento\Quote\Model\QuoteFactory;

/**
 * Test class for Fedex\Shipment\Controller\Adminhtml\shipment\Delete
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class DeleteorderTest extends \PHPUnit\Framework\TestCase
{
    protected $orderMock;
    protected $quoteMock;
    protected $toggleMock;
    /** @var ObjectManagerHelper|MockObject */
    protected $objectManagerHelper;

    /** @var Context|MockObject */
    protected $context;

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
        $this->context = $this->createMock(Context::class);
        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->createMock(Http::class);
        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
                    ->disableOriginalConstructor()
                    ->getMock();

        $this->orderMock = $this->getMockBuilder(Order::class)
            ->setMethods(['load', 'delete'])
            ->disableOriginalConstructor()
            ->getMock();

         $this->quoteMock = $this->getMockBuilder(QuoteFactory::class)
            ->setMethods(['create', 'load', 'delete'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleMock = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
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
            Deleteorder::class,
            [
                'context' => $this->context,
                'registry' => $this->registry,
                'orderModel' => $this->orderMock,
                'toggleConfig' => $this->toggleMock,
                '_request' => $this->requestMock,
                'messageManager' => $this->messageManager,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'resultPageFactory' => $this->pageFactory,
                'quoteFactory' => $this->quoteMock
            ]
        );
    }

    /**
     * Test testExecute method.
     */
    public function testExecute()
    {
        $this->requestMock->expects($this->any())->method('getParam')->willReturn("2");
        
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturnSelf();

        $this->toggleMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->orderMock->expects($this->any())->method('load')->willReturnSelf();

        $this->quoteMock->expects($this->any())->method('create')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('load')->willReturnSelf();

        $this->quoteMock->expects($this->any())->method('delete')->willReturnSelf();

        $this->orderMock->expects($this->any())->method('delete')->willReturnSelf();

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
        $this->messageManager->expects($this->any())->method('addErrorMessage')->willReturn("This item no longer exists.");
        $this->resultRedirectFactory->expects($this->any())->method('setPath')->willReturnSelf("*/*/");
        $this->assertEquals($this->resultRedirectFactory, $this->delete->execute());
    }
}
