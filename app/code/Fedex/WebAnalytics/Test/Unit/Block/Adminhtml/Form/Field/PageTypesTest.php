<?php
/**
 * @category    Fedex
 * @package     Fedex_WebAnalytics
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Test\Unit\Block\Adminhtml\Form\Field;

use Fedex\WebAnalytics\Block\Adminhtml\Form\Field\PageTypes;
use PHPUnit\Framework\TestCase;

class PageTypesTest extends TestCase
{
    /**
     * @var PageTypes
     */
    private PageTypes $pageTypes;

    protected function setUp(): void
    {
        $this->pageTypes = $this->getMockBuilder(PageTypes::class)
            ->disableOriginalConstructor()->setMethodsExcept(['addColumn', 'getColumns'])
            ->getMock();
    }

    public function testPrepareToRender(): void
    {
        $class = new \ReflectionClass($this->pageTypes);
        $method = $class->getMethod('_prepareToRender');
        $method->setAccessible(true);
        $method->invoke($this->pageTypes);
        $this->assertEquals([
            PageTypes::LABEL_FORM_KEY, PageTypes::VALUE_FORM_KEY
        ], array_keys($this->pageTypes->getColumns()));
    }
}
