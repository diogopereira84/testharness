<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Delivery\Test\Unit\Model\Source;

use Fedex\Delivery\Model\Source\ShippingOptions;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\TestCase;

/**
 * Test class for shipping options
 */
class ShippingOptionsTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    /**
     * @var ShippingOptions $objShippingOptions
     */
    protected $objShippingOptions;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->objShippingOptions = $this->objectManagerHelper->getObject(
            ShippingOptions::class,
            []
        );
    }

    /**
     * Test getConfig
     *
     * @return void
     */
    public function testToOptionArray()
    {
        $this->assertIsArray($this->objShippingOptions->toOptionArray());
    }
}
