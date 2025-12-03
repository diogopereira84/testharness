<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Test\Unit\Controller\Account;

use Fedex\EnhancedProfile\Controller\Account\Preferences;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Fedex\Ondemand\Model\Config as OndemandConfig;

/**
 * Test class for Preferences
 */
class PreferencesTest extends TestCase
{
    protected $ondemandConfigMock;
    protected $preferences;
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

        $this->ondemandConfigMock = $this->getMockBuilder(OndemandConfig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMyAccountTabNameValue'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->preferences = $this->objectManagerHelper->getObject(
            Preferences::class,
            [
                'resultPageFactory' => $this->resultPageFactory,
                'resultRedirectFactory' => $this->redirectFactory,
                'config' => $this->ondemandConfigMock
            ]
        );
    }

    /**
     * Test execute method
     *
     * @return void
     */
    public function testExecute()
    {
        $this->resultPageFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->ondemandConfigMock->expects($this->any())
            ->method('getMyAccountTabNameValue')
            ->willReturn('My Account | FedEx Office');

        $this->assertEquals($this->resultPageFactory, $this->preferences->execute());
    }

}
