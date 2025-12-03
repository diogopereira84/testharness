<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare (strict_types = 1);

namespace Fedex\SSO\Test\Unit\Model;

use Fedex\SSO\Helper\Data;
use Fedex\SSO\Model\ProfileManagement;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Test class for ProfileManagementTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class ProfileManagementTest extends TestCase
{
    protected $helper;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $data;
    /**
     * Test setUp
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->helper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerProfile', 'getFCLCookieNameToggle', 'getFCLCookieConfigValue'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->data = $this->objectManager->getObject(
            ProfileManagement::class,
            [
                'helper' => $this->helper
            ]
        );
    }

    /**
     * Function test Is Get Customer Logged In
     *
     * @return void
     */
    public function testIsCustomerLoggedIn()
    {
        $this->helper->expects($this->any())
            ->method('getCustomerProfile')
            ->willReturn(true);
        $this->helper->expects($this->any())
            ->method('getFCLCookieNameToggle')
            ->willReturn(true);
        $this->helper->expects($this->any())
            ->method('getFCLCookieConfigValue')
            ->willReturn('sdffcdzfa');

        $this->assertEquals(true, $this->data->isCustomerLoggedIn());
    }

    /**
     * Function test Is Get Customer Logged In with toggle off
     *
     * @return void
     */
    public function testIsCustomerLoggedInWithToggleOff()
    {
        $this->helper->expects($this->any())
            ->method('getCustomerProfile')
            ->willReturn(true);
        $this->helper->expects($this->any())
            ->method('getFCLCookieNameToggle')
            ->willReturn(false);

        $this->assertEquals(true, $this->data->isCustomerLoggedIn());
    }
}
