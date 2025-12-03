<?php

namespace Fedex\Company\Test\Unit\Model\Source;

use Fedex\Company\Model\Source\ShippingOptions;
use PHPUnit\Framework\TestCase;

class ShippingOptionsTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $model;
    protected function setUp(): void
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $this->objectManager->getObject(ShippingOptions::class);
    }

    /**
     * Test for toOptionArray method.
     *
     * @return array
     */
    public function testToOptionArray()
    {
        $response = [
            ['value' => 'GROUND_US', 'label' => __('Ground US')],
            ['value' => 'LOCAL_DELIVERY_AM', 'label' => __('FedEx Local Delivery AM')],
            ['value' => 'LOCAL_DELIVERY_PM', 'label' => __('FedEx Local Delivery PM')],
            ['value' => 'EXPRESS_SAVER', 'label' => __('Express Saver')],
            ['value' => 'TWO_DAY', 'label' => __('2 Day')],
            ['value' => 'STANDARD_OVERNIGHT', 'label' => __('Standard Overnight')],
            ['value' => 'PRIORITY_OVERNIGHT', 'label' => __('Priority Overnight')],
            ['value' => 'FIRST_OVERNIGHT', 'label' => __('First Overnight')],
        ];

        $result = $this->model->toOptionArray();
        $expectedResult = $response;
        $this->assertEquals($expectedResult, $result);
    }
}
