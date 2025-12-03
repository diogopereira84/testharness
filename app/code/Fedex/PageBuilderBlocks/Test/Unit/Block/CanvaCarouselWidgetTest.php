<?php
/**
 * @category Fedex
 * @package Fedex_PageBuilderBlocks
 * @copyright Copyright (c) 2024 FedEx
 */

declare(strict_types=1);

namespace Fedex\PageBuilderBlocks\Test\Unit\Block;

use PHPUnit\Framework\TestCase;
use Magento\Framework\View\Element\Template\Context;
use Fedex\PageBuilderBlocks\Block\CanvaCarouselWidget;

class CanvaCarouselWidgetTest extends TestCase
{
    /**
     * @var CanvaCarouselWidget
     */
    private $block;

    /**
     * @var Context
     */
    private $contextMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->block = new CanvaCarouselWidget(
            $this->contextMock,
            ['canva_tag_id' => '123456789']
        );
    }

    /**
     * @return void
     */
    public function testGetCanvaTagId()
    {
        $this->assertEquals('123456789', $this->block->getCanvaTagId());
    }
}
