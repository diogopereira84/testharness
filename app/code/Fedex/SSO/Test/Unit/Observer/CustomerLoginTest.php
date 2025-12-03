<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);
namespace Fedex\SSO\Test\Unit\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\SSO\Model\Login as LoginModal;
use Fedex\SSO\Observer\CustomerLogin;
use Magento\Framework\Event\Observer;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\View\Layout;
use Magento\Framework\View\Model\Layout\Merge;

/**
 * Test class for CustomerLoginTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class CustomerLoginTest extends TestCase
{
    protected $layoutMock;
    protected $layoutMergeMock;
    protected $_observer;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $customerlogin;
    /**
     * @var LoginModal $loginMock
     */
    protected $loginMock;

     /**
     * @var ToggleConfig $toogleConfigMock
     */
    protected $toogleConfigMock;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->loginMock = $this->getMockBuilder(LoginModal::class)
            ->setMethods(
                [
                    'isCustomerLoggedIn'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->toogleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(
                [
                    'getToggleConfigValue'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->layoutMock = $this->getMockBuilder(Layout::class)
            ->setMethods(
                [
                    'getUpdate',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->layoutMergeMock = $this->getMockBuilder(Merge::class)
            ->setMethods(
                [
                    'getHandles'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->_observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData','getFullActionName'])
            ->getMock();
        $this->objectManager = new ObjectManager($this);
        $this->customerlogin = $this->objectManager->getObject(
            CustomerLogin::class,
            [
                'login' => $this->loginMock,
                'toogleConfigInterface'=>$this->toogleConfigMock
            ]
        );
    }

    /**
     * Test execute function
     *
     * @return void
     */
    public function testExecute()
    {
        $this->_observer->expects($this->any())->method('getData')->willreturn($this->layoutMock);
        $this->layoutMock->expects($this->any())->method('getUpdate')->willreturn($this->layoutMergeMock);
        $this->layoutMergeMock->expects($this->any())->method('getHandles')->willreturn(['checkout_cart_index']);
        $this->_observer->expects($this->any())->method('getFullActionName')->willreturn('company_users_index');
        $this->toogleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->assertNotNull($this->customerlogin->execute($this->_observer));
    }
}
