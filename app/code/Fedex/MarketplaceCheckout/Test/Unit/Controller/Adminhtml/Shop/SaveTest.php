<?php

declare(strict_types=1);

use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Fedex\MarketplaceCheckout\Controller\Adminhtml\Shop\Save;
use Mirakl\Core\Model\Shop;
use Magento\Backend\Model\Session;
use Magento\Framework\Serialize\Serializer\Json;

class SaveTest extends TestCase
{
    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $contextMock;

    /**
     * @var Shop|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $shopModelMock;

    /**
     * @var Session|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $adminSessionMock;

    /**
     * @var Json|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $jsonMock;

    /**
     * @var ManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $messageManagerMock;

    /**
     * @var RedirectFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $redirectFactoryMock;

    /**
     * @var Redirect|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $redirectMock;

    /**
     * @var Http|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestMock;

    /**
     * @var Save
     */
    private $saveAction;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shopModelMock = $this->getMockBuilder(Shop::class)
            ->disableOriginalConstructor()
            ->addMethods(['setShippingMethods'])
            ->onlyMethods(['save', 'load'])
            ->getMock();

        $this->adminSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->addMethods(['setFormData'])
            ->getMock();

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageManagerMock = $this->getMockBuilder(ManagerInterface::class)
            ->getMock();

        $this->redirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->redirectMock->expects($this->any())
            ->method('setPath')
            ->willReturnSelf();

        $this->redirectFactoryMock = $this->getMockBuilder(RedirectFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->redirectFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->redirectMock);

        $this->contextMock->expects($this->any())
            ->method('getResultRedirectFactory')
            ->willReturn($this->redirectFactoryMock);

        $this->contextMock->expects($this->any())
            ->method('getMessageManager')
            ->willReturn($this->messageManagerMock);

        $sessionMock = $this->getMockBuilder(Session::class)
            ->addMethods(['setFormData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock->expects($this->any())
            ->method('getSession')
            ->willReturn($sessionMock);

        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->saveAction = new Save(
            $this->contextMock,
            $this->shopModelMock,
            $this->adminSessionMock,
            $this->jsonMock
        );
    }

    /**
     * Configure valid case with data
     */
    private function configureCaseWithData()
    {
        $data = ['shipping_method' => ['method_1', 'method_2']];
        $postData = ['id' => 1, 'shipping_method' => $data['shipping_method']];

        $this->requestMock->expects($this->any())
            ->method('getPostValue')
            ->willReturn($postData);
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturn(1);

        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->shopModelMock->expects($this->any())
            ->method('load')
            ->with(1);

        $this->jsonMock->expects($this->any())
            ->method('serialize')
            ->with($data['shipping_method'])
            ->willReturn(json_encode($data['shipping_method']));

        $this->shopModelMock->expects($this->any())
            ->method('setShippingMethods')
            ->with(json_encode($data['shipping_method']));

        $this->shopModelMock->expects($this->any())
            ->method('save');
    }

    /**
     * Test the execute method
     */
    public function testExecute()
    {
        $this->configureCaseWithData();

        $message = 'Shipping Methods have been saved.';
        $this->messageManagerMock->expects($this->any())
            ->method('addSuccess')
            ->with($message);

        $result = $this->saveAction->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * Test the execute method with exception
     */
    public function testExecuteWithException()
    {
        $this->configureCaseWithData();

        $message = 'Shipping Methods have been saved.';
        $this->messageManagerMock->expects($this->any())
            ->method('addSuccess')
            ->with($message)
            ->will($this->throwException(new Exception()));

        $result = $this->saveAction->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * Test the execute method with runtime exception
     */
    public function testExecuteWithRuntimeException()
    {
        $this->configureCaseWithData();

        $message = 'Shipping Methods have been saved.';
        $this->messageManagerMock->expects($this->any())
            ->method('addSuccess')
            ->with($message)
            ->will($this->throwException(new RuntimeException()));

        $result = $this->saveAction->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * Test the execute method with localized exception
     */
    public function testExecuteWithLocalizedException()
    {
        $this->configureCaseWithData();

        $message = 'Shipping Methods have been saved.';
        $this->messageManagerMock->expects($this->any())
            ->method('addSuccess')
            ->with($message)
            ->will($this->throwException(new LocalizedException(__('test'))));

        $result = $this->saveAction->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * Test the execute method without data
     */
    public function testExecuteWithoutData()
    {
        $postData = false;

        $this->requestMock->expects($this->any())
            ->method('getPostValue')
            ->willReturn($postData);
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturn(1);

        $result = $this->saveAction->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }
}
