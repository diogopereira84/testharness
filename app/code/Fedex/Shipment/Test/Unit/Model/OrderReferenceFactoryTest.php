<?php

namespace Fedex\Shipment\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Fedex\Shipment\Model\OrderReferenceFactory;

/**
 * Test class for Fedex\Shipment\Model\OrderReferenceFactory
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class OrderReferenceFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    /** @var ObjectManager |MockObject */
    protected $objectManager;

    /** @var OrderReferenceFactory |MockObject */
    protected $orderReferenceFactory;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->orderReferenceFactory = $this->objectManagerHelper->getObject(
            OrderReferenceFactory::class
        );
    }

    /**
     * Test testCreate
     */
    public function testCreate()
    {
        $this->assertEquals(null, $this->orderReferenceFactory->create());
    }
}
