<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Controller\Index;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Ondemand\Model\Config as OndemandConfig;
use Fedex\UploadToQuote\Controller\Index\QuoteHistory;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page as ResultPage;
use Magento\Customer\Model\Session as CustomerSession;
use Fedex\Base\Helper\Auth;

class QuoteHistoryTest extends TestCase
{
    protected $ondemandConfigMock;
    protected $toggleConfigMock;
    protected $pageMock;
    protected $pageConfig;
    protected $pageTitle;
    protected $quoteHistory;
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var PageFactory|MockObject
     */
    protected $resultPageFactory;

    /**
     * @var RedirectFactory|MockObject
     */
    protected $redirectFactoryMock;

    /**
     * @var CustomerSession $customerSession
     */
    protected $customerSessionMock;

    protected Auth|MockObject $baseAuthMock;
    private Page|MockObject $resultPage;

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->resultPageFactory = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'addHandle'])
            ->getMock();

        $this->redirectFactoryMock = $this->getMockBuilder(RedirectFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSessionMock = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['isLoggedIn'])
            ->getMock();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();

        $this->ondemandConfigMock = $this->getMockBuilder(OndemandConfig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMyAccountTabNameValue'])
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getToggleConfigValue'])
            ->getMock();

        $this->pageMock = $this->getMockBuilder(ResultPage::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfig'])
            ->getMock();

        $this->pageConfig = $this->getMockBuilder(Config::class)
            ->onlyMethods(['getTitle'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageTitle = $this->getMockBuilder(Title::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['set'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->quoteHistory = $this->objectManagerHelper->getObject(
            QuoteHistory::class,
            [
                'resultPageFactory' => $this->resultPageFactory,
                'resultRedirectFactory' => $this->redirectFactoryMock,
                'customerSession' => $this->customerSessionMock,
                'authHelper' => $this->baseAuthMock,
                'toggleConfig' => $this->toggleConfigMock,
                'config' => $this->ondemandConfigMock
            ]
        );
    }

    /**
     * Test execute.
     *
     * @return void
     */
    public function testExecute()
    {
        $this->resultPage = $this->createMock(Page::class);
        $this->resultPageFactory->expects($this->any())->method('create')->willReturn($this->resultPage);
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(false);
        $redirectMock = $this->getMockBuilder(Redirect::class)
            ->setMethods(['setPath'])->disableOriginalConstructor()->getMock();
        $this->redirectFactoryMock->expects($this->any())->method('create')->willReturn($redirectMock);

        $this->assertNull($this->quoteHistory->execute());
    }

    /**
     * Test execute else.
     *
     * @return void
     */
    public function testExecuteNotNull()
    {
        $this->resultPage = $this->createMock(Page::class);
        $this->resultPageFactory->expects($this->any())->method('create')->willReturn($this->resultPage);
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(true);

        $this->assertIsObject($this->quoteHistory->execute());
    }

}
