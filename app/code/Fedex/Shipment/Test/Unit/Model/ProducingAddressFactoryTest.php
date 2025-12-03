<?php

namespace Fedex\Shipment\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Fedex\Shipment\Model\ProducingAddressFactory;

/**
 * Test class for Fedex\Shipment\Model\ProducingAddressFactory
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class ProducingAddressFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectManager |MockObject */
    protected $objectManagerHelper;

    /** @var ProducingAddressFactory |MockObject */
    protected $producingAddressFactory;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->producingAddressFactory = $this->objectManagerHelper->getObject(
            ProducingAddressFactory::class
        );
    }

    /**
     * Test testConstruct
     */
    public function testCreate()
    {
        $this->assertEquals(null, $this->producingAddressFactory->create());
    }
}
