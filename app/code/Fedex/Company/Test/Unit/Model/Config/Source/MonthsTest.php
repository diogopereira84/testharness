<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Company\Test\Unit\Model\Config\Source;

use Fedex\Company\Model\Config\Source\Months;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class MonthsTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $model;
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->model = $this->objectManager->getObject(Months::class);
    }

    /**
     * @test testToOptionArray
     */
    public function testToOptionArray()
    {
        $months = [
            ['value' => '', 'label' => __('0 - Month')],
            ['value' => '01', 'label' => __('1 - January')],
            ['value' => '02', 'label' => __('2 - February')],
            ['value' => '03', 'label' => __('3 - March')],
            ['value' => '04', 'label' => __('4 - April')],
            ['value' => '05', 'label' => __('5 - May')],
            ['value' => '06', 'label' => __('6 - June')],
            ['value' => '07', 'label' => __('7 - July')],
            ['value' => '08', 'label' => __('8 - August')],
            ['value' => '09', 'label' => __('9 - September')],
            ['value' => '10', 'label' => __('10 - October')],
            ['value' => '11', 'label' => __('11 - November')],
            ['value' => '12', 'label' => __('12 - December')],
        ];

        $this->assertEquals($months, $this->model->toOptionArray());
    }
}
