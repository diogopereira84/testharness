<?php
/**
 * @category    Fedex
 * @package     Fedex_WebAnalytics
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Test\Unit\Block\Adminhtml\Form\Field\Widget;

use Fedex\WebAnalytics\Block\Adminhtml\Form\Field\Widget\DynamicRowsStaticValue;
use PHPUnit\Framework\TestCase;

class DynamicRowsStaticValueTest extends TestCase
{
    /**
     * @var DynamicRowsStaticValue
     */
    private DynamicRowsStaticValue $dynamicRowsStaticValue;

    protected function setUp(): void
    {
        $this->dynamicRowsStaticValue = $this->getMockBuilder(DynamicRowsStaticValue::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['addColumn', 'getColumns'])
            ->getMock();
    }

    public function testPrepareToRender(): void
    {
        $class = new \ReflectionClass($this->dynamicRowsStaticValue);
        $method = $class->getMethod('_prepareToRender');
        $method->setAccessible(true);
        $method->invoke($this->dynamicRowsStaticValue);
        $this->assertEquals([
            DynamicRowsStaticValue::VALUE, DynamicRowsStaticValue::PARAMETER_TO_URL
        ], array_keys($this->dynamicRowsStaticValue->getColumns()));
    }
}
