<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UpSellIt\Test\Unit\ViewModel;

use Fedex\UpSellIt\ViewModel\UpSellit;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\SSO\ViewModel\SsoConfiguration;

/**
 * Prepare test objects.
 */
class UpSellitTest extends TestCase
{
    protected $upSellitMock;
    /**
     * @var SsoConfiguration
     */
    protected $ssoConfigurationMock;

    /**
     * @var MockObject|ObjectManager
     */
    protected $objectManager;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->ssoConfigurationMock = $this->getMockBuilder(SsoConfiguration::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isRetail'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->upSellitMock = $this->objectManager->getObject(
            UpSellit::class,
            [
                'ssoConfiguration' => $this->ssoConfigurationMock
            ]
        );
    }

    /**
     * @test getIsRetail
     *
     * @return void
     */
    public function testGetIsRetail()
    {
        $expectedResult = true;

        $this->ssoConfigurationMock->expects($this->once())->method('isRetail')
            ->willReturn(true);

        $this->assertEquals($expectedResult, $this->upSellitMock->getIsRetail());
    }
}
