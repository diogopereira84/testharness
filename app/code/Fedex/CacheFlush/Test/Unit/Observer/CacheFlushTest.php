<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CacheFlush\Test\Unit\Observer;

use Fedex\CacheFlush\Observer\CacheFlush;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class CacheFlushTest extends \PHPUnit\Framework\TestCase
{
    protected $cacheFlush;
    /** @var TypeListInterface|MockObject $cacheTypeListMock */
    private $cacheTypeListMock;

    /**
     * Setup
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->cacheTypeListMock = $this->getMockForAbstractClass(TypeListInterface::class);

        $objectManager = new ObjectManager($this);
        $this->cacheFlush = $objectManager->getObject(
            CacheFlush::class,
            [
                'cacheTypeListMock' => $this->cacheTypeListMock
            ]
        );
    }

    /**
     * Test flush cache to call flushCache method
     * @return void
     */
    public function testExecute()
    {
        /** @var Observer|MockObject $eventObserver */
        $eventObserver = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertEquals('', $this->cacheFlush->execute($eventObserver));
    }
}
