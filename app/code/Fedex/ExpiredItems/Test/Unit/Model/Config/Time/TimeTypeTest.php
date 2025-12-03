<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpiredItems\Test\Unit\Model\Config\Time;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\ExpiredItems\Model\Config\Time\TimeType;

/**
 * Test Class for system configuration Time Type class
 */
class TimeTypeTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $model;
    /**
     * Set up method
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->model = $this->objectManager->getObject(TimeType::class);
    }

    /**
     * Test for toOptionArray method.
     *
     * @return void
     */
    public function testToOptionArray()
    {
        $response = [
        ['value' => 'days', 'label' => __('Days')],
        ['value' => 'hours', 'label' => __('Hours')],
        ['value' => 'minutes', 'label' => __('Minutes')]
        ];

        $this->assertEquals($response, $this->model->toOptionArray());
    }
}
