<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Test\Unit\Controller\Index;

use PHPUnit\Framework\TestCase;
use Fedex\SSO\Model\Login as LoginModal;
use Fedex\SSO\Controller\Customer\Login;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Test class for LoginTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class LoginTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $loginMock;
    /**
     * @var LoginModal
     */
    protected LoginModal $login;

    /**
     * Test setUp
     * 
     * @return void
     */
    protected function setUp(): void
    {
        $this->login = $this->getMockBuilder(LoginModal::class)
            ->setMethods(['isCustomerLoggedIn'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->loginMock = $this->objectManager->getObject(
            Login::class,
            [
                'login' => $this->login,

            ]
        );
    }

    /**
     * Test Execute With customer login
     * 
     * @return void
     */
    public function testIsCustomerLoggedIn()
    {
        $this->login->expects($this->any())->method('isCustomerLoggedIn')->willReturn(1);
        $this->assertEquals(true, $this->loginMock->execute());
    }
}
