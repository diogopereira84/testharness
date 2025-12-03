<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Test\Unit\Model\Frontend\Toggle;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\EnvironmentManager\Model\Frontend\Toggle\E376312ProfileApiFailureMessaging;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use PHPUnit\Framework\TestCase;

/*
 * E376312ProfileApiFailureMessagingTest
 */
class E376312ProfileApiFailureMessagingTest extends TestCase
{
    /**
     * Test build with enable
     *
     * @return void
     */
    public function testBuildEnabled()
    {
        $toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $secureHtmlRendererMock = $this->getMockBuilder(SecureHtmlRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $toggle = new E376312ProfileApiFailureMessaging($toggleConfigMock, $secureHtmlRendererMock);
        $secureHtmlRendererMock->expects($this->once())->method('renderTag')
            ->with(
                'script',
                ['type' => 'text/javascript'],
                E376312ProfileApiFailureMessaging::SCRIPT_TOGGLE_ENABLE,
                false
            )
            ->willReturn('<script>window.mazegeeks_profile_api_failure_messaging = true</script>');
        $this->assertEquals(
            '<script>window.mazegeeks_profile_api_failure_messaging = true</script>',
            $toggle->build()
        );
    }
}
