<?php

namespace Fedex\SelfReg\Controller\Ajax;

use Magento\Framework\App\Action\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\Page as ResultPage;
use Magento\Framework\View\Result\PageFactory;
use Fedex\Commercial\ViewModel\CommercialSsoConfiguration;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Fedex\Cart\ViewModel\UnfinishedProjectNotification;
use Fedex\UploadToQuote\ViewModel\QuoteHistory;

class LoginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Login
     */
    protected $controller;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PageFactory
     */
    protected $resultPageFactoryMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CommercialSsoConfiguration
     */
    protected $commercialSsoConfigurationMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|UploadToQuoteViewModel
     */
    protected $uploadToQuoteViewModelMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|QuoteHistory
     */
    protected $QuoteHistoryMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|UnfinishedProjectNotification
     */
    protected $unfinishedProjectNotificationMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->resultPageFactoryMock = $this->createMock(PageFactory::class);
        $this->commercialSsoConfigurationMock = $this->createMock(CommercialSsoConfiguration::class);
        $this->uploadToQuoteViewModelMock = $this->createMock(UploadToQuoteViewModel::class);
        $this->QuoteHistoryMock = $this->createMock(QuoteHistory::class);
        $this->unfinishedProjectNotificationMock = $this->createMock(UnfinishedProjectNotification::class);

        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->controller = $objectManager->getObject(
            Login::class,
            [
                'context' => $contextMock,
                'resultPageFactory' => $this->resultPageFactoryMock,
                'commercialSsoConfiguration' => $this->commercialSsoConfigurationMock,
                'uploadToQuoteViewModel' => $this->uploadToQuoteViewModelMock,
                'quoteHistory' => $this->QuoteHistoryMock,
                'unfinishedProjectNotification' => $this->unfinishedProjectNotificationMock
            ]
        );
    }

    /**
     * Test execute method
     */
    public function testExecute()
    {
        $blockContent = '<div>Block content</div>';

        $resultPageMock = $this->getMockBuilder(ResultPage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $layoutMock = $this->getMockBuilder(\Magento\Framework\View\Layout::class)
            ->disableOriginalConstructor()
            ->getMock();
        $blockMock = $this->getMockBuilder(\Magento\Framework\View\Element\Template::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPageFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($resultPageMock);

        $resultPageMock->expects($this->once())
            ->method('getLayout')
            ->willReturn($layoutMock);

        $layoutMock->expects($this->once())
            ->method('createBlock')
            ->willReturn($blockMock);

        $blockMock->expects($this->once())
            ->method('setTemplate')
            ->willReturnSelf();

        $blockMock->expects($this->exactly(4))
            ->method('setData')
            ->willReturnSelf();

        $blockMock->expects($this->once())
            ->method('toHtml')
            ->willReturn($blockContent);

        $responseMock = $this->getMockBuilder(\Magento\Framework\App\ResponseInterface::class)
            ->setMethods(['setBody'])
            ->getMockForAbstractClass();
        $responseMock->expects($this->once())
            ->method('setBody')
            ->with($blockContent);

        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->once())
            ->method('getResponse')
            ->willReturn($responseMock);

        $this->controller->__construct(
            $contextMock,
            $this->resultPageFactoryMock,
            $this->commercialSsoConfigurationMock,
            $this->uploadToQuoteViewModelMock,
            $this->QuoteHistoryMock,
            $this->unfinishedProjectNotificationMock
        );

        $this->assertNull($this->controller->execute());
    }
}
