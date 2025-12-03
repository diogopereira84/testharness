<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\ProductCustomAtrribute\Test\Unit\Model\Config;

use Fedex\ProductCustomAtrribute\Model\Config\AbstractConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Phrase;

/**
 * Test class for AbstractConfig
 */
class AbstractConfigTest extends TestCase
{
    protected $scopeConfigInterface;
    protected $abstractConfig;
    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->scopeConfigInterface = $this->getMockBuilder(ScopeConfigInterface::class)
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->abstractConfig = $this->getMockForAbstractClass(
            AbstractConfig::class,
            [
                'scopeConfig' => $this->scopeConfigInterface
            ]
        );
    }

    /**
     * Test getCanvaLink function
     *
     * @return string
     */
    public function testGetCanvaLink()
    {
        $this->scopeConfigInterface->expects($this->any())->method('getValue')->willReturn("Test");
        $this->assertSame("Test", $this->abstractConfig->getCanvaLink());
    }
}
