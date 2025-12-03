<?php

namespace Fedex\Shipment\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Fedex\Shipment\Model\OrderValueFactory;

/**
 * Test class for Fedex\Shipment\Model\OrderValueFactory
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class OrderValueFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectManager |MockObject */
    protected $objectManagerHelper;

    /** @var OrderValueFactory |MockObject */
    protected $orderValueFactory;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->orderValueFactory = $this->objectManagerHelper->getObject(
            OrderValueFactory::class
        );
    }

    /**
     * Test testCreate
     */
    public function testCreate()
    {
        $this->assertEquals(null, $this->orderValueFactory->create());
    }
}
