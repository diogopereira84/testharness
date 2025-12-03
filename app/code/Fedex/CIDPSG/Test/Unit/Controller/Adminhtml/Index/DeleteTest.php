<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Controller\Adminhtml\Customer;

use Fedex\CIDPSG\Controller\Adminhtml\Index\Delete;
use Fedex\CIDPSG\Model\Customer;
use Magento\Framework\App\RequestInterface;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test class for Delete
 */
class DeleteTest extends TestCase
{
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /** @var ObjectManagerHelper|MockObject */
    protected $objectManagerHelper;

    /** @var Registry|MockObject */
    protected $registry;

    /** @var RequestInterface|MockObject */
    protected $requestMock;

    /** @var ManagerInterface|MockObject */
    protected $messageManager;

    /** @var Customer|MockObject */
    protected $customer;

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

        $this->requestMock = $this->createMock(RequestInterface::class);

        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customer = $this->getMockBuilder(Customer::class)
            ->setMethods(['load', 'delete', 'getClientId'])
            ->disableOriginalConstructor()
            ->getMock();

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

        $this->delete = $this->objectManagerHelper->getObject(
            Delete::class,
            [
                'customer' => $this->customer,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'requestInterface' => $this->requestMock,
                'messageManager' => $this->messageManager,
                'logger' => $this->loggerMock,
                'resultPageFactory' => $this->pageFactory
            ]
        );
    }

    /**
     * Test testExecute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $this->requestMock->expects($this->once())->method('getParam')->with("entity_id")->willReturn("2");
        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->customer->expects($this->once())->method('load')->willReturnSelf();
        $this->customer->expects($this->any())->method('delete')->willReturn(true);
        $this->messageManager->expects($this->any())->method('addSuccess')
        ->willReturn("This item no longer exists.");
        $this->resultRedirectFactory->expects($this->once())->method('setPath')->willReturnSelf("*/*/");
        $this->assertEquals($this->resultRedirectFactory, $this->delete->execute());
    }

    /**
     * Test testExecute method with Default Customer.
     *
     * @return void
     */
    public function testExecuteWithDefaultCustomer()
    {
        $this->requestMock->expects($this->once())->method('getParam')->with("entity_id")->willReturn("2");
        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->customer->expects($this->once())->method('load')->willReturnSelf();
        $this->customer->expects($this->once())->method('getClientId')->willReturn('default');
        $this->customer->expects($this->any())->method('delete')->willReturn(true);
        $this->messageManager->expects($this->any())->method('addSuccess')
        ->willReturn("This item no longer exists.");
        $this->resultRedirectFactory->expects($this->once())->method('setPath')->willReturnSelf("*/*/");
        $this->assertEquals($this->resultRedirectFactory, $this->delete->execute());
    }

    /**
     * Test testExecute method.
     *
     * @return void
     */
    public function testExecuteWithoutId()
    {
        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->messageManager->expects($this->once())->method('addError')
        ->willReturn("This item no longer exists.");
        $this->resultRedirectFactory->expects($this->once())->method('setPath')->willReturnSelf("*/*/");
        $this->assertEquals($this->resultRedirectFactory, $this->delete->execute());
    }

    /**
     * Test testExecute method.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $this->requestMock->expects($this->once())->method('getParam')->with("entity_id")->willReturn("2");
        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturnSelf();
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->customer->expects($this->once())->method('load')->willThrowException($exception);
        $this->messageManager->expects($this->once())->method('addError')
        ->willReturn("This item no longer exists.");
        $this->resultRedirectFactory->expects($this->once())->method('setPath')->willReturnSelf("*/*/");
        $this->assertEquals($this->resultRedirectFactory, $this->delete->execute());
    }
}
