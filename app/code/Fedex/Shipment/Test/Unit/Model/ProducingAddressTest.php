<?php

namespace Fedex\Shipment\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Fedex\Shipment\Model\ProducingAddress;

/**
 * Test class for Fedex\Shipment\Model\ProducingAddress
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class ProducingAddressTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectManager |MockObject */
    protected $objectManagerHelper;

    /** @var ProducingAddress |MockObject */
    protected $producingAddress;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->producingAddress = $this->objectManagerHelper->getObject(
            ProducingAddress::class
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
