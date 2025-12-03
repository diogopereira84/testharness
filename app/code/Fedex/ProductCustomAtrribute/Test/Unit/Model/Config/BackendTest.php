<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\ProductCustomAtrribute\Test\Unit\Model\Config;

use Fedex\ProductCustomAtrribute\Model\Config\Backend;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Phrase;

/**
 * Test class for Backend
 */
class BackendTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $backend;
    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->backend = $this->objectManager->getObject(
            Backend::class,
            [
            ]
        );
    }

    /**
     * Test getConfigPrefix function
     *
     * @return string
     */
    public function testGetConfigPrefix()
    {
        $testMethod = new \ReflectionMethod(
            Backend::class,
            'getConfigPrefix'
        );
        $testMethod->setAccessible(true);
        $this->assertSame("fedex/canva_link", $testMethod->invoke($this->backend));
    }
}
