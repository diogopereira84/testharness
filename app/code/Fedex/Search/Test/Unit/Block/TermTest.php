<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\Search\Test\Unit\Block;

use Fedex\Search\Block\Term;
use Magento\Framework\View\LayoutInterface;
use Magento\Theme\Block\Html\Breadcrumbs;
use PHPUnit\Framework\TestCase;

/**
 * Test class Term
 */
class TermTest extends TestCase
{
    /**
     * @var Term
     */
    private Term $term;

    /**
     * @var Breadcrumbs
     */
    private Breadcrumbs $breadcrumbs;

    /**
     * @var LayoutInterface
     */
    private LayoutInterface $layout;

    /**
     * Test setup
     */
    public function setUp(): void
    {
        $this->breadcrumbs = $this->createMock(Breadcrumbs::class);
        $this->breadcrumbs->method('addCrumb')->willReturnSelf();
        $this->layout = $this->createMock(LayoutInterface::class);
        $storeManager = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $store = $this->getMockBuilder(\Magento\Store\Api\Data\StoreInterface::class)
            ->addMethods(['getBaseUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $store->method("getBaseUrl")->willReturn("test.com");
        $storeManager->method('getStore')->willReturn($store);

        $this->term = $this->getMockBuilder(Term::class)
            ->onlyMethods(['getLayout'])
            ->disableOriginalConstructor()
            ->getMock();

        $reflection = new \ReflectionClass(Term::class);
        $reflection_property = $reflection->getProperty('_storeManager');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($this->term, $storeManager);

        $this->term->method('getLayout')->willReturn($this->layout);
    }

    /**
     * Test _prepareLayout function result when active
     *
     * @return void
     */
    public function testPrepareLayout(): void
    {
        $this->layout->method('getBlock')->willReturn($this->breadcrumbs);
        $result = $this->term->_prepareLayout();
        $this->assertEquals($this->term, $result);
    }

    /**
     * Test _prepareLayout function result when inactive
     *
     * @return void
     */
    public function testPrepareLayoutNegative(): void
    {
        $this->layout->method('getBlock')->willReturn(false);
        $result = $this->term->_prepareLayout();
        $this->assertEquals($this->term, $result);
    }
}
