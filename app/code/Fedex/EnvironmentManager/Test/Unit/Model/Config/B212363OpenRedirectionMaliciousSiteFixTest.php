<?php
/**
 * @category  Fedex
 * @package   Fedex_EnvironmentManager
 * @author    Sawai Singh Rajpurohit <sawai.rajpurohit.osv@fedex.com>
 * @copyright 2025 Fedex
 */
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Test\Unit\Model\Config;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\EnvironmentManager\Model\Config\B212363OpenRedirectionMaliciousSiteFix;

class B212363OpenRedirectionMaliciousSiteFixTest extends TestCase
{
    /**
     * Toggle system configuration path
     */
    private const PATH = 'b_2123653_oauth_fix_redirection_to_malicious_site';

    /**
     * @var MockObject|ToggleConfig
     */
    private MockObject|ToggleConfig $toggleConfigMock;

     /**
     * @var B212363OpenRedirectionMaliciousSiteFix
     */
    private B212363OpenRedirectionMaliciousSiteFix $openRedirectionMaliciousSiteFix;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->openRedirectionMaliciousSiteFix = new B212363OpenRedirectionMaliciousSiteFix($this->toggleConfigMock);
    }

    /**
     * Test isActive() method when toggle enabled
     *
     * @return void
     */
    public function testIsActiveTrue(): void
    {
        $this->toggleConfigMock
            ->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(self::PATH)
            ->willReturn(true);
        $this->assertTrue($this->openRedirectionMaliciousSiteFix->isActive());
    }

    /**
     * Test isActive() method when toggle disabled
     *
     * @return void
     */
    public function testIsActiveFalse(): void
    {
        $this->toggleConfigMock
            ->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(self::PATH)
            ->willReturn(false);
        $this->assertFalse($this->openRedirectionMaliciousSiteFix->isActive());
    }
}
