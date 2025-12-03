<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 */
declare(strict_types=1);

namespace Fedex\Cart\Plugin;

use Fedex\Cart\Plugin\CustomerWorkSpacePlugin;
use Magento\Framework\App\Http\Context;
use Fedex\Cart\ViewModel\UnfinishedProjectNotification;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerWorkSpacePluginTest extends TestCase
{
    protected $contextMock;
    protected $unfinishedProjectNotificationMock;
    protected $customerWorkSpacePlugin;
    /**
     * Setup
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->unfinishedProjectNotificationMock = $this->getMockBuilder(UnfinishedProjectNotification::class)
            ->setMethods(['isProjectAvailable'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->customerWorkSpacePlugin = $objectManager->getObject(
            CustomerWorkSpacePlugin::class,
            [
                'unfinishedProjectNotification' => $this->unfinishedProjectNotificationMock
            ]
        );
    }

    /**
     * Test for beforeGetVaryString()
     */
    public function testBeforeGetVaryString()
    {
        $this->unfinishedProjectNotificationMock->expects($this->once())->method('isProjectAvailable')->willReturn(true);

        $this->assertNull($this->customerWorkSpacePlugin->beforeGetVaryString($this->contextMock));
    }
}
