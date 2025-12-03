<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Test\Unit\Controller\Account;

use Fedex\EnhancedProfile\Controller\Account\AccountsAndCreditCards;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Page\Config;
use Fedex\Ondemand\Model\Config as OndemandConfig;

/**
 * Test class for Fedex\EnhancedProfile\Controller\Account\AccountsAndCreditCards
 */
class AccountsAndCreditCardsTest extends TestCase
{
    protected $toggleConfigMock;
    protected $pageMock;
    protected $pageConfig;
    protected $pageTitle;
    protected $ondemandConfigMock;
    protected $accountsAndCreditCards;
    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManagerHelper;

    /**
     * @var PageFactory|MockObject
     */
    protected $resultPageFactory;

    /**
     * @var Redirect|MockObject
     */
    protected $resultRedirect;

    /**
     * @var RedirectFactory|MockObject
     */
    protected $redirectFactory;

    /**
     * Test setUp
     */
    public function setUp(): void
    {
        $this->resultPageFactory = $this->getMockBuilder(PageFactory::class)
                                        ->setMethods(['create'])
                                        ->disableOriginalConstructor()
                                        ->getMock();

        $this->redirectFactory = $this->getMockBuilder(RedirectFactory::class)
                                        ->disableOriginalConstructor()
                                        ->getMock();
        $this->resultRedirect = $this->getMockBuilder(Redirect::class)
                                        ->disableOriginalConstructor()
                                        ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getToggleConfigValue'])
            ->getMock();

        $this->pageMock = $this->getMockBuilder(Page::class)
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

        $this->ondemandConfigMock = $this->getMockBuilder(OndemandConfig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMyAccountTabNameValue'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->accountsAndCreditCards = $this->objectManagerHelper->getObject(
            AccountsAndCreditCards::class,
            [
                'resultPageFactory' => $this->resultPageFactory,
                'resultRedirectFactory' => $this->redirectFactory,
                'toggleConfig' => $this->toggleConfigMock,
                'config' => $this->ondemandConfigMock
            ]
        );
    }

    /**
     * Test Execute method
     *
     * @return void
     */
    public function testExecute()
    {
        $this->resultPageFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->pageMock);
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->pageMock->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->any())
            ->method('getTitle')
            ->willReturn($this->pageTitle);
        $this->pageTitle->expects($this->any())
            ->method('set')
            ->willReturnSelf();
        $this->ondemandConfigMock->expects($this->any())
            ->method('getMyAccountTabNameValue')
            ->willReturn('My Account | FedEx Office');

        $this->assertNotNull($this->accountsAndCreditCards->execute());
    }

}
