<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
*/

namespace Fedex\CatalogMvp\Test\Unit\Model\Source;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Fedex\CatalogMvp\Model\Source\PendingReviewStatuses;

class PendingReviewStatusesTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $catalogPendingReviewStatusOptionsMock;
    /**
     * Setup method
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->catalogPendingReviewStatusOptionsMock = $this->objectManager->getObject(PendingReviewStatuses::class, []);
    }

    /**
     * Test option array method
     *
     * @return void
     */
    public function testGetAllOptions() : void
    {
        $this->assertIsArray($this->catalogPendingReviewStatusOptionsMock->getAllOptions());
    }
}
