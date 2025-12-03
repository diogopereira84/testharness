<?php

namespace Fedex\Shipment\Test\Unit\Controller\Adminhtml\Index;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Fedex\Shipment\Controller\Adminhtml\Index\Index;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Fedex\Shipment\Controller\Adminhtml\Index\Index
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class IndexTest extends TestCase
{
    /** @var ObjectManager|MockObject */
    protected $objectManagerHelper;

    /** @var Index|MockObject */
    protected $index;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->index = $this->objectManagerHelper->getObject(
            Index::class
        );
    }
    
    /**
     * Test testExecute method.
     */
    public function testExecute()
    {
        $this->assertEquals(null, $this->index->execute());
    }
}
