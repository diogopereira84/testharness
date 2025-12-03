<?php
/**
 * @category    Fedex
 * @package     Fedex_WebAnalytics
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Test\Unit\Block\Adminhtml\Form\Field\Widget;

use Fedex\WebAnalytics\Block\Adminhtml\Form\Field\Widget\DynamicRowsFromUrl;
use PHPUnit\Framework\TestCase;

class DynamicRowsFromUrlTest extends TestCase
{
    /**
     * @var DynamicRowsFromUrl
     */
    private DynamicRowsFromUrl $dynamicRowsFromUrl;

    protected function setUp(): void
    {
        $this->dynamicRowsFromUrl = $this->getMockBuilder(DynamicRowsFromUrl::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['addColumn', 'getColumns'])
            ->getMock();
    }

    public function testPrepareToRender(): void
    {
        $class = new \ReflectionClass($this->dynamicRowsFromUrl);
        $method = $class->getMethod('_prepareToRender');
        $method->setAccessible(true);
        $method->invoke($this->dynamicRowsFromUrl);
        $this->assertEquals([
            DynamicRowsFromUrl::REQUEST_PARAM, DynamicRowsFromUrl::PARAMETER_TO_URL
        ], array_keys($this->dynamicRowsFromUrl->getColumns()));
    }
}
