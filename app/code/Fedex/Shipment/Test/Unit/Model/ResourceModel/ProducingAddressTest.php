<?php

namespace Fedex\Shipment\Test\Unit\Model\ResourceModel;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Test class for Fedex\Shipment\Model\ResourceModel\ProducingAddress
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class ProducingAddressTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    /**
     * @var object
     */
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
            \Fedex\Shipment\Model\ResourceModel\ProducingAddress::class
        );
    }

    public function testConstruct()
    {
        $this->assertTrue(true);
    }
}
