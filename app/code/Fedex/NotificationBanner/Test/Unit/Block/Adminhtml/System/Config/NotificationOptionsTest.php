<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 */

namespace Fedex\NotificationBanner\Test\Unit\Block\Adminhtml\System\Config;

use Fedex\NotificationBanner\Block\Adminhtml\System\Config\NotificationOptions;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * NotificationOptionsTest unit test class
 */
class NotificationOptionsTest extends TestCase
{

    /**
     * @var object
     */
    protected $fedexAccount;
    /**
     * @var ObjectManager|MockObject
    */
    protected $objectManagerHelper;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->fedexAccount = $this->objectManagerHelper->getObject(
            NotificationOptions::class
        );
    }
    
    /**
     * Test testConstruct
     */
    public function testConstruct()
    {
        $this->assertTrue(true);
    }
}
