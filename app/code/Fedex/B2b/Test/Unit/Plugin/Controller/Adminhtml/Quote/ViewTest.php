<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\B2b\Test\Unit\Plugin\Controller\Adminhtml\Quote;

use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Request\Http;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\Framework\Controller\ResultFactory;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\B2b\Plugin\Controller\Adminhtml\Quote\View;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\NegotiableQuote\Controller\Adminhtml\Quote\View as Subject;


/**
 * Class ViewTest.
 */
class ViewTest extends TestCase
{

    /**
     * @var (\Magento\Quote\Model\QuoteRepository & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteRepoMock;
    /**
     * Sample Name
     * @var string
     */
    const QUOTE_ID = 5;
    protected $resultPageMock;
    protected $requestMock;
    protected $actionFlagMock;
    protected $loggerMock;
    protected $quoteRepositoryMock;
    protected $negotiableQuoteManagementMock;
    protected $messageProviderMock;
    protected $negotiableCartMock;
    protected $quoteHelperMock;
    protected $sessionManagerInterfaceMock;
    protected $messageManagerInterfaceMock;
    protected $resultFactoryMock;
    protected $quoteView;
    protected $objectManager;
    protected $toggleConfigMock;
    protected $redirectFactoryMock;
    protected $subjectMock;
    protected  $cartDataMock;
    protected $pageConfigMock;
    protected $pageTitleMock;
    protected $redirectMock;


    
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->actionFlagMock = $this->getMockBuilder(\Magento\Framework\App\ActionFlag::class)
            ->disableOriginalConstructor()
            ->getMock();     
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['critical', 'error'])
            ->getMockForAbstractClass();
        $this->quoteRepositoryMock = $this->getMockBuilder(CartRepositoryInterface::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->negotiableQuoteManagementMock = $this->getMockBuilder(NegotiableQuoteManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->messageProviderMock = $this->getMockBuilder(\Magento\NegotiableQuote\Model\Discount\StateChanges\Provider::class)
            ->setMethods(['getChangesMessages'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableCartMock = $this->getMockBuilder(\Magento\NegotiableQuote\Model\Cart::class)
            ->setMethods(['removeAllFailed'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteHelperMock = $this->getMockBuilder(\Magento\NegotiableQuote\Helper\Quote::class)
            ->setMethods(['isLockMessageDisplayed'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->sessionManagerInterfaceMock = $this->getMockBuilder(\Magento\Framework\Session\SessionManagerInterface::class)
            ->setMethods(['start', 'setAdminQuoteView'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->messageManagerInterfaceMock = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->setMethods(['addWarningMessage'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteRepoMock = $this->getMockBuilder(\Magento\Quote\Model\QuoteRepository::class)
                        ->disableOriginalConstructor()
                        ->getMock();
        $this->cartDataMock = $this->getMockBuilder(\Magento\Quote\Api\Data\CartInterface::class)
                        ->disableOriginalConstructor()
                        ->getMockForAbstractClass();                          
        $this->resultPageMock = $this->getMockBuilder(\Magento\Framework\View\Result\Page::class)
                      ->setMethods(['setActiveMenu', 'addBreadcrumb', 'getConfig'])
                        ->disableOriginalConstructor()
                        ->getMock();              
        $this->redirectFactoryMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\RedirectFactory::class)
                        ->setMethods(['create'])
                        ->disableOriginalConstructor()
                        ->getMock();                              
        $this->objectManager = new ObjectManager($this);
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
        ->setMethods(['getToggleConfigValue'])
        ->disableOriginalConstructor()
        ->getMock(); 
        $this->subjectMock = $this->getMockBuilder(Subject::class)
        ->setMethods(['getRequest','execute'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->pageConfigMock = $this->getMockBuilder(\Magento\Framework\View\Page\Config::class)
        ->setMethods(['getTitle'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->pageTitleMock = $this->getMockBuilder(\Magento\Framework\View\Page\Title::class)
        ->setMethods(['prepend'])
        ->disableOriginalConstructor()
        ->getMock(); 
        $this->redirectMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\Redirect::class)
                         ->setMethods(['setPath'])
                         ->disableOriginalConstructor()
                         ->getMock();
        $this->quoteView = $this->objectManager->getObject(
            View::class,
            [
                'logger' => $this->loggerMock,
                'cartRepositoryInterface' => $this->quoteRepositoryMock,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagementMock,
                'messageProvider' => $this->messageProviderMock,
                'cart' => $this->negotiableCartMock,
                'negotiableQuoteHelper' => $this->quoteHelperMock,
                'resultFactory' => $this->resultFactoryMock,
                'sessionManagerInterface' => $this->sessionManagerInterfaceMock,
                'messageManager' => $this->messageManagerInterfaceMock,
                'actionFlag' => $this->actionFlagMock,
                'resultRedirectFactory' => $this->redirectFactoryMock,
                'toggleConfig' => $this->toggleConfigMock
            ]
        );
    }
    
    /**
     * Prepare Request Mock.
     *
     * @return void
     */
    private function prepareRequestMock()
    {
        $quoteId = static::QUOTE_ID;
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['quote_id', null, static::QUOTE_ID]
                ]
            );
    }

    /**
     * @test execute
     *
     * @return void
     */
    public function testExecute()
    {
        $this->prepareRequestMock();
        $proceed = function(){
            return $this->subjectMock->execute();
        };
        $this->subjectMock->expects($this->once())->method('getRequest')->willReturn($this->requestMock);
        $this->sessionManagerInterfaceMock->expects($this->once())->method('start')->willReturnSelf();
        $this->sessionManagerInterfaceMock->expects($this->once())->method('setAdminQuoteView')->with(1);//willReturnSelf();
        $this->quoteRepositoryMock->expects($this->any())->method('get')->with(static::QUOTE_ID, ['*'])->willReturn($this->cartDataMock);
        $this->negotiableCartMock->expects($this->any())->method('removeAllFailed')->willReturnSelf();
        $this->messageProviderMock->expects($this->any())->method('getChangesMessages')->willReturn(['1' => 'Test Message']);
        $this->quoteHelperMock->expects($this->any())->method('isLockMessageDisplayed')->willReturn(true);
        $this->messageManagerInterfaceMock->expects($this->any())->method('addWarningMessage')->willReturnSelf(); 
        $this->resultFactoryMock->expects($this->any())->method('create')->willReturn($this->resultPageMock);
        $this->resultPageMock->expects($this->any())->method('getConfig')->willReturn($this->pageConfigMock);
        $this->pageConfigMock->expects($this->any())->method('getTitle')->willReturn($this->pageTitleMock);
        $this->pageTitleMock->expects($this->any())->method('prepend')->willReturnSelf();        
        $result = $this->quoteView->aroundExecute($this->subjectMock,$proceed);
        $this->assertInstanceOf(\Magento\Framework\View\Result\Page::class, $result);
    }

}
    
