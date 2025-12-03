<?php

namespace Fedex\Shipment\Test\Unit\Controller\Adminhtml\Order;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception;
use Magento\Framework\Phrase;
use Magento\Backend\App\Action\Context;
use Fedex\Shipment\Helper\ShipmentEmail;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Fedex\Shipment\Controller\Adminhtml\Order\Email;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use \Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

/**
 * Test class for Fedex\Shipment\Controller\Adminhtml\Order\Email
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class EmailTest extends TestCase
{
    /** @var ObjectManagerHelper|MockObject */
    protected $objectManagerHelper;

    /** @var Context|MockObject */
    protected $context;

    /** @var ShipmentEmail|MockObject */
    protected $shipmentEmail;

    /** @var Http|MockObject */
    protected $requestMock;

    /** @var ManagerInterface|MockObject */
    protected $messageManager;

    /** @var RedirectFactory|MockObject */
    protected $resultRedirectFactory;

    /** @var OrderRepositoryInterface|MockObject */
    protected $orderRepositoryInterface;

    /** @var LoggerInterface|MockObject */
    protected $loggerMock;

    /** @var Email|MockObject */
    protected $email;
    private MockObject|Order $order;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->context = $this->createMock(Context::class);

        $this->shipmentEmail = $this->getMockBuilder(ShipmentEmail::class)
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->requestMock = $this->createMock(Http::class);

        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
                                ->disableOriginalConstructor()
                                ->getMockForAbstractClass();

        $this->resultRedirectFactory =
                                    $this->getMockBuilder(RedirectFactory::class)
                                    ->setMethods(['create', 'setPath'])
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->orderRepositoryInterface = $this->getMockBuilder(OrderRepositoryInterface::class)
                                            ->setMethods(['get'])
                                            ->disableOriginalConstructor()
                                            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->email = $this->objectManagerHelper->getObject(
            Email::class,
            [
                'context' => $this->context,
                'shipmentEmail' => $this->shipmentEmail,
                '_request' => $this->requestMock,
                'messageManager' => $this->messageManager,
                'orderRepository' => $this->orderRepositoryInterface,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'logger' => $this->loggerMock,
            ]
        );
    }

    /**
     * Test testExecute method.
     */
    public function testExecute()
    {
        $this->order = $this->getMockBuilder(Order::class)
                ->disableOriginalConstructor()
                    ->getMock();
        $this->requestMock->expects($this->any())->method('getParam')->with("order_id")->willReturn("2");
        $values = ['id'=>'2'];
        $this->orderRepositoryInterface->expects($this->any())->method('get')->willReturn($this->order);
        $varienObject = new \Magento\Framework\DataObject();
        $varienObject->setData($values);
        $this->order->expects($this->any())->method('getShipmentsCollection')->willReturn([$varienObject]);
        $this->shipmentEmail->expects($this->any())->method('sendEmail')->willReturn("sent");
        $this->messageManager->expects($this->any())->method('addSuccessMessage')
        ->with("You sent the order email.")->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())->method('setPath')->willReturn("sales/order/view");
        $this->assertEquals("sales/order/view", $this->email->execute());
    }

    /**
     * Test testExecute method without sent
     */
    public function testExecuteWithoutSent()
    {
        $this->order = $this->getMockBuilder(Order::class)
                ->disableOriginalConstructor()
                    ->getMock();
        $this->requestMock->expects($this->any())->method('getParam')->with("order_id")->willReturn("2");
        $values = ['id'=>'2'];
        $this->orderRepositoryInterface->expects($this->any())->method('get')->willReturn($this->order);
        $varienObject = new \Magento\Framework\DataObject();
        $varienObject->setData($values);
        $this->order->expects($this->any())->method('getShipmentsCollection')->willReturn([$varienObject]);
        $this->shipmentEmail->expects($this->any())->method('sendEmail')->willReturn("error");
        $this->messageManager->expects($this->any())->method('addErrorMessage')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())->method('setPath')->willReturn("sales/order/view");
        $this->assertEquals("sales/order/view", $this->email->execute());
    }

    /**
     * Test testExecuteWithLocalizedException method.
     */
    public function testExecuteWithLocalizedException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->order = $this->getMockBuilder(Order::class)
                ->disableOriginalConstructor()
                    ->getMock();
        $this->requestMock->expects($this->any())->method('getParam')->with("order_id")->willReturn("2");
        $this->orderRepositoryInterface->expects($this->any())->method('get')->willThrowException($exception);
        $this->messageManager->expects($this->any())->method('addErrorMessage')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())->method('setPath')->willReturn("sales/order/view");
        $this->assertEquals("sales/order/view", $this->email->execute());
    }

    /**
     * Test testExecuteWithException method.
     */
    public function testExecuteWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $this->order = $this->getMockBuilder(Order::class)
                        ->disableOriginalConstructor()
                        ->getMock();
        $this->requestMock->expects($this->any())->method('getParam')->with("order_id")->willReturn("2");
        $this->orderRepositoryInterface->expects($this->any())->method('get')->willThrowException(new \Exception());
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())->method('setPath')->willReturn("sales/order/view");
        $this->assertEquals("sales/order/view", $this->email->execute());
    }

    /**
     * Test testExecuteWithoutParam method.
     */
    public function testExecuteWithoutParam()
    {
        $phrase = new Phrase(__('Exception message'));
        $this->order = $this->getMockBuilder(Order::class)
                        ->disableOriginalConstructor()
                        ->getMock();
        $this->requestMock->expects($this->any())->method('getParam')->with("order_id")->willReturn("");
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())->method('setPath')->willReturn("sales/order/view");
        $this->assertEquals("sales/order/view", $this->email->execute());
    }
}
