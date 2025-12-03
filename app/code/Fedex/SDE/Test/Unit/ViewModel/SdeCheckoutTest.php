<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare (strict_types = 1);

namespace Fedex\SDE\Test\Unit\ViewModel;

use Fedex\SDE\Helper\SdeHelper;
use Fedex\SDE\ViewModel\SdeCheckout;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Test class for SdeCheckoutTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class SdeCheckoutTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $sdeConfigurationMock;
    /**
     * @var Sdehelper $sdeHelperMock
     */
    protected $sdeHelper;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->sdeHelper = $this->getMockBuilder(SdeHelper::class)
            ->setMethods(['getIsSdeStore'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->sdeConfigurationMock = $this->objectManager->getObject(
            SdeCheckout::class,
            [
                'sdeHelper' => $this->sdeHelper,
            ]
        );
    }

    /**
     * @test testGetIsSdeStore
     */
    public function testgetIsSdeStore()
    {
        $this->sdeHelper->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(1);

        $this->assertEquals(true, $this->sdeConfigurationMock->getIsSdeStore());
    }

    /**
     * @test testGetIsSdeStoreWithToggleOff
     */
    public function testgetIsSdeStoreWithToggleOff()
    {
        $this->sdeHelper->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(0);

        $this->assertEquals(false, $this->sdeConfigurationMock->getIsSdeStore());
    }
}
